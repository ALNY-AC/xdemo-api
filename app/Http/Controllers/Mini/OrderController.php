<?php

namespace App\Http\Controllers\Mini;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    use ResponseJson;

    public function list(Request $request)
    {

        $user_id = $request->get('jwt')->id;
        $store_id = $request->input('store_id');
        $state = $request->input('state');

        $storeInfo = DB::table('stores')
            ->where([
                ['id', '=', $store_id],
//                ['user_id', '=', $user_id],
            ])
            ->first();

        if (!$storeInfo) {
            return $this->jsonData(-1, 'ERR');
        }

        $DB = DB::table('order')
            ->select('order.*', 'order_address.contacts', 'order_address.phone', 'order_address.p', 'order_address.c', 'order_address.a', 'order_address.address', 'order_address.address_num', 'order_address.x', 'order_address.y', 'order_address.tag')
            ->leftJoin('order_address', 'order.address_id', '=', 'order_address.id');

        if ($state || $state === 0) {
            $DB->where([
                ['state', '=', $state],
                ['state', '!=', 0],
                ['order.store_id', '=', $store_id],
                ['type', '=', 'water_order'],
            ])
                ->orWhere([
                    ['state', '=', $state],
                    ['state', '!=', 0],
                    ['order.store_id', '=', $store_id],
                    ['type', '=', 'pay_order'],
                ]);
        } else {
            $DB->where([
                ['order.store_id', '=', $store_id],
                ['type', '=', 'pay_order'],
                ['state', '!=', 0],
            ])
                ->orWhere([
                    ['order.store_id', '=', $store_id],
                    ['type', '=', 'water_order'],
                    ['state', '!=', 0],
                ]);
        }
        $DB->orderBy('order.add_time', 'desc');

        $total = $DB->count();

        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10));
        }

        if ($request->filled('page_size')) {
            $DB->limit($request->input('page_size', 10));
        }

        $result = $DB->get();

        $result->map(function ($item) {

            $item->snapshotInfo = DB::table('snapshot')->where('order_id', $item->order_id)->get();

            $item->snapshotInfo = $item->snapshotInfo->map(function ($el) {
                $el->data = json_decode($el->data, true);
                return $el;
            });
            return $item;
        });

        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData($result->count(), 'OK', $data);
    }

    public function list_count(Request $request)
    {
        $user_id = $request->get('jwt')->id;
        $store_id = $request->input('store_id');

        $storeInfo = DB::table('stores')
            ->where([
                ['id', '=', $store_id],
//                ['user_id', '=', $user_id],
            ])
            ->first();

        if (!$storeInfo) {
            return $this->jsonData(-1, 'ERR');
        }
        //全部
        $data = [];
        $data['total'] = DB::table('order')
            ->where([
                ['state', '!=', 0],
                ['store_id', '=', $store_id],
                ['type', '!=', 'water_coupon'],
            ])
            ->count();
        //待配送
        $data['wait_send'] = DB::table('order')
            ->where([
                ['state', '=', 1],
                ['store_id', '=', $store_id],
                ['type', '!=', 'water_coupon'],
            ])
            ->count();
        //配送中
        $data['sending'] = DB::table('order')
            ->where([
                ['state', '=', 2],
                ['store_id', '=', $store_id],
                ['type', '!=', 'water_coupon'],
            ])
            ->count();
        //退款售后
        $data['refund'] = DB::table('order')
            ->where([
                ['state', '=', 21],
                ['store_id', '=', $store_id],
                ['type', '!=', 'water_coupon'],
            ])
            ->count();
        //已完成
        $data['finish'] = DB::table('order')
            ->where([
                ['state', '=', 4],
                ['store_id', '=', $store_id],
                ['type', '!=', 'water_coupon'],
            ])
            ->count();
        //已取消
        $data['off'] = DB::table('order')
            ->where([
                ['state', '=', 5],
                ['store_id', '=', $store_id],
                ['type', '!=', 'water_coupon'],
            ])
            ->count();

        return $this->jsonData(1, 'OK', $data);
    }

    public function info(Request $request)
    {

        $order_id = $request->input('order_id');
        if (!$order_id) {
            return $this->jsonData(-1, 'ERR order_id');
        }
        $DB = DB::table('order')->orderBy('add_time', 'desc');
        $result = $DB->where('order_id', $order_id)->first();

        $result->snapshotInfo = DB::table('snapshot')->where('order_id', $result->order_id)->get();
        $result->addressInfo = DB::table('order_address')->where('id', $result->address_id)->first();

        $result->snapshotInfo = $result->snapshotInfo->map(function ($el) {
            $el->data = json_decode($el->data, true);
            return $el;
        });

        if (!$result) {
            return $this->jsonData(-1, 'ERR');
        }
        return $this->jsonData(1, 'OK', $result);
    }

    //商家取消订单
    public function closeOrder(Request $request)
    {
        $order_id = $request->input('order_id', '');
        $orderInfo = DB::table('order')
            ->where('order_id', $order_id)
            ->first();
        if (!$orderInfo) {
            return $this->jsonData(-1, '订单号不存在');
        }
        if ($orderInfo->type != 'pay_order') {
            return $this->jsonData(-1, '非普通支付订单，暂不可退款');
        }

        $data = [];
        $data['orderId'] = $orderInfo->pay_id;
        $data['pay_price'] = $orderInfo->price * 100;
        $data['refund_desc'] = '';
        //在订单待支付，完成不能取消订单
        $state = $orderInfo->state;
        if ($state === 0 || $state === 4) {
            return $this->jsonData(-1, 'err store close');
        }
        if ($orderInfo) {
            $res = $this->refund($data);

            if ($res) {
                DB::beginTransaction();
                $update_res = DB::table('order')
                    ->where([
                        ['order_id', '=', $order_id],
                        ['type', '=', 'pay_order'],
                    ])
                    ->update([
                        'state' => 5
                    ]);

                $update_row = DB::table('pay')
                    ->where([
                        ['pay_id', '=', $orderInfo->pay_id]
                    ])
                    ->update([
                        'state' => 3
                    ]);

                if ($update_res && $update_row) {
                    DB::commit();
                    return $this->jsonData(1, 'OK');
                } else {
                    DB::rollback();
                    return $this->jsonData(-1, 'err');
                }

            } else {
                return $this->jsonData(-1, 'ERR');
            }
        }
    }

    //商家送货
    public function sending(Request $request)
    {
        $order_id = $request->input('order_id', '');
        $orderInfo = DB::table('order')
            ->where('order_id', $order_id)
            ->first();
        if (!$orderInfo) {
            return $this->jsonData(-1, '订单号不存在');
        }

        //
        $state = $orderInfo->state;
        if ($state === 0 || $state === 2 || $state === 4 || $state === 5 || $state === 21) {
            return $this->jsonData(-1, 'err store close');
        }
        $res = DB::table('order')
            ->where([
                ['order_id', '=', $order_id],
            ])
            ->update([
                'state' => 2
            ]);
        if (!$res) {
            return $this->jsonData(-1, 'ERR');
        }
        return $this->jsonData(1, 'OK');
    }

    //退款
    public function refund($data)
    {
        // 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
        $miniConfig = [
            'sandbox' => false,
            'app_id' => env('WX_WECHAT_APP_ID'),
            'mch_id' => env('WX_PAY_MCH_ID'),
            'key' => env('WX_PAY_KEY'),
            'cert_path' => config_path('wx_cert/apiclient_cert.pem'),    // XXX: 绝对路径！！！！
            'key_path' => config_path('wx_cert/apiclient_key.pem'),      // XXX: 绝对路径！！！！
            'notify_url' => '', // 默认支付结果通知地址
            'refund_notify_url' => url('api/mini/pay/wx_refund_notify_url'), // 默认支付退款结果通知地址
        ];

        $payment = Factory::payment($miniConfig);
        $refund = $payment->refund->byOutTradeNumber($data['orderId'], $data['orderId'], $data['pay_price'], $data['pay_price'], [
            'refund_desc' => $data['refund_desc'],
            'notify_url' => $miniConfig['refund_notify_url'],
        ]);
        Log::info('退款申请返回信息:' . json_encode($refund, JSON_UNESCAPED_UNICODE));
        if ($refund['return_code'] === 'FAIL') {
            return false;
        }
        if ($refund['result_code'] === 'FAIL') {
            return false;
        }
        Log::info('退款申请成功：' . $refund['out_trade_no']);
        return true;
    }

    public function wx_refund_notify_url()
    {
        $config = [
            'app_id' => env('WX_WECHAT_APP_ID'),
            'mch_id' => env('WX_PAY_MCH_ID'),
            'key' => env('WX_PAY_KEY'),
        ];

        $wx = Factory::payment($config);
        $response = $wx->handleRefundedNotify(function ($message, $reqInfo, $fail) {
            if ($message['return_code'] === 'SUCCESS') {
                if ($reqInfo['refund_status'] === 'SUCCESS') {
                    $pay = DB::table('pay')
                        ->where('pay_id', $reqInfo['out_trade_no'])
                        ->first();

                    if (!$pay) {
                        Log::info('支付单不存在：' . json_encode($pay, JSON_UNESCAPED_UNICODE));
                        return true;
                    }

                    DB::table('pay')
                        ->where('pay_id', $reqInfo['out_trade_no'])
                        ->update([
                            'state' => 4
                        ]);
                    return true;
                }
            }
            Log::info(json_encode($reqInfo, JSON_UNESCAPED_UNICODE));
            Log::info(json_encode($fail, JSON_UNESCAPED_UNICODE));

            return true;
        });
        echo $response;
    }

    //完成订单
    public function success(Request $request)
    {
        $order_id = $request->input('order_id', '');
        $orderInfo = DB::table('order')
            ->where([
                ['order_id', '=', $order_id],
            ])
            ->first();
        if (!$orderInfo) {
            return $this->jsonData(-1, '订单号不存在');
        }

        $state = $orderInfo->state;
        //订单待支付，完成，订单取消不能确认收货
        if ($state === 0 || $state === 4 || $state === 5) {
            return $this->jsonData(-1, '订单错误，不能确认收货');
        }

        if ($orderInfo->type === 'pay_order') {
            $data = [];
            $data['store_id'] = $orderInfo->store_id;
            $data['money'] = $orderInfo->re_price - $orderInfo->total_royalty;
            $data['type'] = 1;
            $data['state'] = 1;
            $data['pay_id'] = $orderInfo->pay_id;
            $data['text'] = '购水';
            $data['budget_type'] = 2;

            DB::beginTransaction();
            $res = DB::table('order')
                ->where('order_id', $orderInfo->order_id)
                ->update([
                    'state' => 4
                ]);
            //商家收入
            $res_store = DB::table('store_profile')
                ->where('store_id', $orderInfo->store_id)
                ->increment('money', $orderInfo->re_price - $orderInfo->total_royalty);

            $row = DB::table('budget')
                ->insert($data);
            if ($res && $row && $res_store) {
                DB::commit();
                return $this->jsonData(1, 'OK');
            } else {
                DB::rollback();
                return $this->jsonData(-1, 'ERR');
            }
        } elseif ($orderInfo->type === 'water_order') {
            $res = DB::table('order')
                ->where('order_id', $orderInfo->order_id)
                ->update([
                    'state' => 4
                ]);
            if ($res) {
                return $this->jsonData(1, 'OK');
            } else {
                return $this->jsonData(-1, 'ERR');
            }
        } else {
            return $this->jsonData(-1, 'ERR');
        }
    }

    //拒绝退款
    public function reject(Request $request)
    {
        $order_id = $request->input('order_id', '');
        $store_remarks = $request->input('store_remarks', '');
        $orderInfo = DB::table('order')
            ->where([
                ['order_id', '=', $order_id],
            ])
            ->first();
        if (!$orderInfo) {
            return $this->jsonData(-1, '订单号不存在');
        }

        $state = $orderInfo->state;
        //只有申请退款
        if ($state != 21) {
            return $this->jsonData(-1, '订单错误，不能确拒绝退款');
        }
        $res = DB::table('order')
            ->where([
                ['order_id', '=', $order_id],
            ])
            ->update([
                'state' => 22,
                'store_remarks' => $store_remarks,
            ]);
        if (!$res) {
            return $this->jsonData(-1, 'ERR');
        }
        return $this->jsonData(1, 'OK');
    }
}

//message
/**
 * {
 * "return_code": "SUCCESS",
 * "appid": "wxb21c49c1f4205110",
 * "mch_id": "1573115611",
 * "nonce_str": "bdf57bf6a8c28524cc0e73b7b90d8ecb",
 * "req_info": "RNCuBT0D5VxXiWwg/mLfbMKqRTc2XzDuF8sHMWfee4eK1TUpCuJYujTpN7VOyBLfvdb2SBoKpEyXL5HqpRj8J/c5o2W/Nsev0H3JgVVwakwHYpx5UdN/EPqzCnN0JCtyMQioVoaxscJNMBZefTsy5lwGZkh2I8ZUiEmTXUVAPBE36KLRKMczP/E/HmK/nZIBn5zIlRw61Wc3IeLAsb7wIrVNYC7N2zkdnwbI3PF+KihvccLBVYYl0dhYfRW0m6bR7nvuwWa9E516JdBkJOXlsIIlC6L9Xg3PKI8tcu7t17XpPXPQEsNUab5vpaqSP8knwmKq5pofw7vDvAicb26V0611pxCkAzwzO+q5/97xu3WVE6oBxGfuY/BUUeknA2ucLI6P4MtCpL8+DHDcnhG6iFt1lcWGQw9T9LmZXzJZpeH+PH6SxIbdpP1bmFRsL8Tk0rg+m4zV5qBumc7fgHlmt1wk8R2aejj5zUHZFZ02HcI8xtp+kJX96KB7Pn9ocaLu4n43cMDFtjrkaT/siXUoX0e3UN874e02R9E64Ew9W2CgoHFTek4TfM49XSSIbAeLh4H7As5/0WZTBW80CKHfhnRxIBwXJCiDMyZVedqSEWRda0kyodYK1oq5dmxOCRgr0EAA6Do7M5AB7QiTKQy/i7h58p48s5A8BSs+nzDtK3EwzhhZpFkT32HqyL8Rw057pSY0mYAwTuJNRb904bCJuS+toCGCBYikJMuWIvvVUrzafcjyhTSeBEekl+btOb1FOlCEs3BpjWSxn5OVd/rMY4Q6jBC9bjhJ8HlZow1cv1hof6pLGLt1FCBXWjgjoBCoCStIlpocpTEcXOkaGBmmyHR86QwFNTn4KvuyaSRPITZOLYbDopT0nq9Oz4Lz4AL3oB5SW2A/bR1Mngjmg/pd4pXqGByCebHmYwQf7OT2Gp/G8yl4RGXdTb8bbRCr4EEg7PJHAQPZO1eUhtvbd4xQhsr2OQZiUgqhLz6DdPRJ7g72S5koxdWJgV0ZrdmijafG42DnRvJFWZV8YWkUdBZtsmlGVZqsyG4ypC+kb+coZYMIU8Swjn0SdPNGAOTisnIA"
 * }
 */
//$reqInfo
/**
 * {
 * "out_refund_no": "PAY2020042218060618263",
 * "out_trade_no": "PAY2020042218060618263",
 * "refund_account": "REFUND_SOURCE_RECHARGE_FUNDS",
 * "refund_fee": "1",
 * "refund_id": "50300604072020042300225680793",
 * "refund_recv_accout": "支付用户零钱",
 * "refund_request_source": "API",
 * "refund_status": "SUCCESS",
 * "settlement_refund_fee": "1",
 * "settlement_total_fee": "1",
 * "success_time": "2020-04-23 15:21:14",
 * "total_fee": "1",
 * "transaction_id": "4200000536202004222148106478"
 * }
 */
