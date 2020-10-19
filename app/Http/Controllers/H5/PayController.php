<?php

namespace App\Http\Controllers\H5;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Mini\SendMsgController;
use App\Http\Response\ResponseJson;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PayController extends Controller
{

    use ResponseJson;

    public function getH5(Request $request)
    {
        $remarks = $request->input('remarks');
        $send_time = $request->input('send_time');
        $payInfo = DB::table('pay')->where('pay_id', $request->input('pay_id'))->first();
        if (!$payInfo) {
            return $this->jsonData(-1, '参数错误');
        }
        DB::table('order')
            ->where('pay_id', $payInfo->pay_id)
            ->update([
                'remarks' => $remarks,
                'send_time' => $send_time,
            ]);

        $userInfo = DB::table('order')
            ->select('users.*')
            ->leftJoin('users', 'order.user_id', '=', 'users.id')
            ->where('pay_id', $payInfo->pay_id)
            ->first();

        $config = [
            // 必要配置
            'app_id' => env('WX_WECHAT_APP_ID'),
            'mch_id' => env('WX_PAY_MCH_ID'),
            'key' => env('WX_PAY_KEY'),   // API 密钥
            'notify_url' => url("api/h5/pay/wx_notify_url"),     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = Factory::payment($config);
        $jssdk = $app->jssdk;

        $result = $app->order->unify([
            'body' => '云水cloud',
            'out_trade_no' => $payInfo->pay_id,
            'total_fee' => $payInfo->price * 100,
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $userInfo->openid,
        ]);
        if ($result['return_code'] != 'SUCCESS') {
            return $this->jsonData(-1, 'ERR', $config);
        }

        Log::info('预支付单：' . json_encode($result, JSON_UNESCAPED_UNICODE));
        $config = $jssdk->sdkConfig($result['prepay_id']); // 返回数组
        return $this->jsonData(1, 'OK', $config);
    }

    public function wx_notify_url()
    {
        $config = [
            'app_id' => env('WX_WECHAT_APP_ID'),
            'mch_id' => env('WX_PAY_MCH_ID'),
            'key' => env('WX_PAY_KEY'),
        ];

        $wx = Factory::payment($config);
        $response = $wx->handlePaidNotify(function ($message, $fail) {
            Log::info('微信回调message：' . json_encode($message, JSON_UNESCAPED_UNICODE));
            Log::info('微信回调fail：' . json_encode($fail, JSON_UNESCAPED_UNICODE));

            $out_trade_no = $message['out_trade_no'];

            $pay = DB::table('pay')
                ->where('pay_id', $out_trade_no)
                ->first();

            if (!$pay) {
                Log::info('支付单不存在：' . json_encode($pay, JSON_UNESCAPED_UNICODE));
                return true;
            }

            $orderInfo = DB::table('order')
                ->select('snapshot.*', 'order.re_price', 'order.total_royalty', 'order.pay_id', 'order_address.contacts', 'order_address.phone', 'order_address.address', 'order_address.address_num', 'users.openid')
                ->leftJoin('snapshot', 'order.order_id', '=', 'snapshot.order_id')
                ->leftJoin('order_address', 'order.address_id', '=', 'order_address.id')
                ->leftJoin('stores', 'order.store_id', '=', 'stores.id')
                ->leftJoin('users', 'stores.user_id', '=', 'users.id')
                ->where('pay_id', $out_trade_no)
                ->first();
            $order_state = 1;
            //水票
            if ($pay->type === 'water_coupon') {
                $order_state = 4;

                $data = json_decode($orderInfo->data, true);
                //查看是否购买过
                $hasWater = DB::table('user_water')
                    ->where([
                        ['water_id', '=', $data['id']],
                        ['user_id', '=', $orderInfo->user_id],
                    ])
                    ->first();
                if (!$hasWater) {
                    $userWater = [];
                    $userWater['water_id'] = $data['id'];
                    $userWater['user_id'] = $orderInfo->user_id;
                    $userWater['store_id'] = $orderInfo->store_id;
                    $userWater['name'] = $data['name'];
                    $userWater['num'] = $data['buy_num'];
                    $userWater['last_num'] = $data['buy_num'];
                    $userWater['price'] = $data['price'];
                    $userWater['goods_id'] = $data['goods_id'];
                    DB::table('user_water')
                        ->insert($userWater);
                } else {
                    DB::table('user_water')
                        ->where('id', $hasWater->id)
                        ->update([
                            'num' => $hasWater->num + $data['buy_num'],
                            'last_num' => $hasWater->last_num + $data['buy_num'],
                        ]);
                }

                //水票收支记录
                $data = [];
                $data['store_id'] = $orderInfo->store_id;
                $data['money'] = $orderInfo->re_price - $orderInfo->total_royalty;
                $data['type'] = 1;
                $data['state'] = 1;
                $data['pay_id'] = $orderInfo->pay_id;
                $data['text'] = '水票购买';
                $data['budget_type'] = 1;
                DB::table('budget')
                    ->insert($data);
                DB::table('store_profile')
                    ->where('store_id', $orderInfo->store_id)
                    ->increment('money', $orderInfo->re_price - $orderInfo->total_royalty);
            }

            $is_order = DB::table('order')
                ->where([
                    ['user_id', '=', $orderInfo->user_id],
                    ['store_id', '=', $orderInfo->store_id],
                    ['state', '=', 4],
                    ['type', '!=', 'water_coupon'],
                ])
                ->first();

            $is_first = 0;
            if (!$is_order) {
                $is_first = 1;
            }

            DB::beginTransaction();
            $res = DB::table('order')
                ->where('pay_id', $message['out_trade_no'])
                ->update([
                    'state' => $order_state,
                    'is_first' => $is_first,
                ]);
            $row = DB::table('pay')
                ->where('pay_id', $message['out_trade_no'])
                ->update([
                    'state' => 2,
                    'info' => json_encode($message)
                ]);

            if ($res && $row) {
                DB::commit();

                $send = [];
                $send['goods_name'] = $orderInfo->title;
                $send['pay_time'] = $pay->edit_time;
                $send['address'] = $orderInfo->address . $orderInfo->address_num;
                $send['contacts'] = $orderInfo->contacts;
                $send['phone'] = $orderInfo->phone;
                //消息推送
                (new SendMsgController())->send('pages/order/order/index', $orderInfo->openid, $send);
                return true;
            } else {
                DB::rollback();
                return true;
            }
        });
        echo $response;
    }
}

//预支付单
/**
 * {
 * "return_code": "SUCCESS",
 * "return_msg": "OK",
 * "appid": "wxb21c49c1f4205110",
 * "mch_id": "1573115611",
 * "nonce_str": "d8DuV2YQ7hRq4bFU",
 * "sign": "D620ACE8B7658451C74FD7523D6700A6",
 * "result_code": "SUCCESS",
 * "prepay_id": "wx22171915751312612d3961331117688300",
 * "trade_type": "JSAPI"
 * }
 */