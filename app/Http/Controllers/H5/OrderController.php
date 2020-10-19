<?php

namespace App\Http\Controllers\H5;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    use ResponseJson;

    public function create(Request $request)
    {

        $SnapshotDB = DB::table('snapshot');
        $AddressDB = DB::table('address');
        $OrderAddressDB = DB::table('order_address');
        $OrderDB = DB::table('order');
        $PayDB = DB::table('pay');

        $order_id = 'ORDER' . Carbon::now()->format('YmdHis') . rand(10000, 99999);
        $pay_id = 'PAY' . Carbon::now()->format('YmdHis') . rand(10000, 99999);

        $goodsArr = $request->input('goods'); //商品
        $store_id = $request->input('store_id');
        $remarks = $request->input('remarks'); // 用户的备注
        $address_id = $request->input('address_id'); // 用户选择的地址，不能直接使用，需要拿出来备份
        $user_id = $request->get('jwt')->id;

        $storeInfo = DB::table('stores')
            ->leftJoin('store_profile', 'stores.id', '=', 'store_profile.store_id')
            ->where('stores.id', $store_id)->first();
        if (!$storeInfo) {
            return $this->jsonData(-1, 'ERR');
        }

        $addressInfo = $AddressDB->where([
            ['id', '=', $address_id],
            ['user_id', '=', $user_id],
        ])
            ->first();
        if (!$addressInfo) {
            return $this->jsonData(-1, '请填写准确的地址');
        }
        $addressInfo = collect($addressInfo)->except(['id', 'edit_time', 'date_state']);

        $snapshotInfoArr = [];
        $price = 0.00;
        $buy_num = 0;

        foreach ($goodsArr as $goods) {
            $buy_num += $goods['quantity'];
            //商品信息
            $goodsInfo = DB::table('goods')->where('id', $goods['id'])->first();
            $goodsInfo = collect($goodsInfo)->except(['sort', 'edit_time', 'date_state']);

            //校验、扣除库存
//            if ($goodsInfo['stock'] < $goods['quantity']) {
//                return $this->jsonData(-1, '库存不足');
//            }
//            DB::table('goods')
//                ->where('id', $goods['id'])
//                ->decrement('stock', $goods['quantity']);

            //快照
            $price += $goodsInfo['price'] * $goods['quantity'];
            $snapshotInfo = [];
            $snapshotInfo['goods_id'] = $goodsInfo['id'];
            $snapshotInfo['order_id'] = $order_id;
            $snapshotInfo['store_id'] = $goodsInfo['store_id'];
            $snapshotInfo['type'] = 'pay_order';
            $snapshotInfo['user_id'] = $user_id;
            $snapshotInfo['title'] = $goodsInfo['title'];
            $snapshotInfo['data'] = collect([$goods, $goodsInfo])->collapse()->toJson();

            $snapshotInfoArr[] = $snapshotInfo;
        }

        $address_id = $OrderAddressDB->insertGetId($addressInfo->toArray());

        $orderInfo['order_id'] = $order_id;
        $orderInfo['pay_id'] = $pay_id;
        $orderInfo['user_id'] = $user_id;
        $orderInfo['store_id'] = $store_id;
        $orderInfo['remarks'] = $remarks;
        $orderInfo['price'] = $price;
        $orderInfo['re_price'] = $price;
        $orderInfo['type'] = 'pay_order';
        $orderInfo['address_id'] = $address_id;
        $orderInfo['coupon_num'] = $buy_num;
        $orderInfo['order_royalty'] = $storeInfo->order_royalty;
        $orderInfo['total_royalty'] = $buy_num * $storeInfo->order_royalty;
        Log::info('订单信息：' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE));

        $payInfo = [];
        $payInfo['pay_id'] = $pay_id;
        $payInfo['store_id'] = $store_id;
        $payInfo['price'] = $price;
        $payInfo['type'] = 'pay_order';

        if ($price <= 0) {
            $orderInfo['state'] = 2;
            $payInfo['state'] = 2;
        }

        $SnapshotDB->insert($snapshotInfoArr);
        $PayDB->insert($payInfo);
        $OrderDB->insert($orderInfo);
        return [
            'code' => 1,
            'msg' => 'success',
            'data' => [
                "pay_id" => $pay_id,
                "order_id" => $order_id
            ],
        ];
    }

    //水票兑换
    public function coupon_exchange(Request $request)
    {
        $SnapshotDB = DB::table('snapshot');
        $AddressDB = DB::table('address');
        $OrderAddressDB = DB::table('order_address');
        $OrderDB = DB::table('order');
        $PayDB = DB::table('pay');

        $order_id = 'ORDER' . Carbon::now()->format('YmdHis') . rand(10000, 99999);
        $pay_id = 'PAY' . Carbon::now()->format('YmdHis') . rand(10000, 99999);

        $goodsArr = $request->input('goods'); //商品
        $remarks = $request->input('remarks'); // 用户的备注
        $address_id = $request->input('address_id'); // 用户选择的地址，不能直接使用，需要拿出来备份
        $user_id = $request->get('jwt')->id;
        $water_coupon_id = $request->input('user_water_coupon_id', '');

        $user_water = DB::table('user_water')
            ->where([
                ['id', '=', $water_coupon_id],
                ['user_id', '=', $user_id],
            ])
            ->first();
        if (!$user_water || !$water_coupon_id) {
            return $this->jsonData(-1, '非法请求');
        }

        $addressInfo = $AddressDB
            ->where([
                ['id', '=', $address_id],
                ['user_id', '=', $user_id],
            ])
            ->first();
        if (!$addressInfo) {
            return $this->jsonData(-1, '请填写准确的地址');
        }
        $addressInfo = collect($addressInfo)->except(['id', 'edit_time', 'date_state']);

        $snapshotInfoArr = [];
        $price = 0;
        $buy_num = 0;
        $type = 'water_order';

        foreach ($goodsArr as $goods) {
            $buy_num += $goods['quantity'];
            if ($buy_num > $user_water->last_num) {
                return $this->jsonData(-1, '您兑款的数量已超出你的水票');
            }
            //商品信息
            $goodsInfo = DB::table('goods')->where('id', $goods['id'])->first();
            $goodsInfo = collect($goodsInfo)->except(['sort', 'edit_time', 'date_state']);

            //校验、扣除库存
//            if ($goodsInfo['stock'] < $goods['quantity']) {
//                return $this->jsonData(-1, '库存不足');
//            }
//            DB::table('goods')
//                ->where('id', $goods['id'])
//                ->decrement('stock', $goods['quantity']);

            //快照
            $snapshotInfo = [];
            $snapshotInfo['goods_id'] = $goodsInfo['id'];
            $snapshotInfo['order_id'] = $order_id;
            $snapshotInfo['store_id'] = $goodsInfo['store_id'];
            $snapshotInfo['type'] = $type;
            $snapshotInfo['user_id'] = $user_id;
            $snapshotInfo['title'] = $goodsInfo['title'];
            $snapshotInfo['data'] = collect([$goods, $goodsInfo])->collapse()->toJson();

            $snapshotInfoArr[] = $snapshotInfo;
        }

        $address_id = $OrderAddressDB->insertGetId($addressInfo->toArray());

        $orderInfo['order_id'] = $order_id;
        $orderInfo['pay_id'] = $pay_id;
        $orderInfo['user_id'] = $user_id;
        $orderInfo['store_id'] = $user_water->store_id;
        $orderInfo['remarks'] = $remarks;
        $orderInfo['price'] = $price;
        $orderInfo['re_price'] = $price;
        $orderInfo['type'] = $type;
        $orderInfo['address_id'] = $address_id;
        $orderInfo['coupon_num'] = $buy_num;
        Log::info('订单信息：' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE));

        $payInfo = [];
        $payInfo['pay_id'] = $pay_id;
        $payInfo['store_id'] = $user_water->store_id;
        $payInfo['price'] = $price;
        $payInfo['type'] = $type;

        if ($price <= 0) {
            $orderInfo['state'] = 1;
            $payInfo['state'] = 2;
        }

        $SnapshotDB->insert($snapshotInfoArr);
        $PayDB->insert($payInfo);
        $OrderDB->insert($orderInfo);
        DB::table('user_water')
            ->where('id', $user_water->id)
            ->decrement('last_num', $buy_num);
        return [
            'code' => 1,
            'msg' => 'success',
            'data' => [
                "pay_id" => $pay_id,
                "order_id" => $order_id
            ],
        ];
    }

    public function list(Request $request)
    {

        $user_id = $request->get('jwt')->id;
        $DB = DB::table('order')
            ->where('user_id', $user_id)
            ->where('state', '!=', 0)
            ->orderBy('add_time', 'desc');

        if ($request->filled('type')) {
            $DB->whereIn('type', $request->input('type'));
        }

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
            if ($item->store_id) {
                $item->storeInfo = DB::table('stores')->where('id', $item->store_id)->first();
            }

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

    public function info(Request $request)
    {

        $DB = DB::table('order')->orderBy('add_time', 'desc');
        $result = $DB->where('order_id', $request->input('order_id'))->first();

        $result->snapshotInfo = DB::table('snapshot')->where('order_id', $result->order_id)->get();
        $result->storeInfo = DB::table('stores')->where('id', $result->store_id)->first();
        $result->payInfo = DB::table('pay')->where('pay_id', $result->pay_id)->first();
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

    //用户取消订单
    public function closeOrder(Request $request)
    {
        $order_id = $request->input('order_id', '');
        $user_id = $request->get('jwt')->id;
        $orderInfo = DB::table('order')
            ->where([
                ['order_id', '=', $order_id],
                ['user_id', '=', $user_id],
                ['type', '=', 'pay_order'],
            ])
            ->first();
        if (!$orderInfo) {
            return $this->jsonData(-1, '订单号不存在');
        }
        //在订单已支付、配送中可取消
        $state = $orderInfo->state;
        if ($state === 1 || $state === 2) {
            if ($orderInfo) {
                DB::table('order')
                    ->where([
                        ['order_id', '=', $order_id]
                    ])
                    ->update([
                        'state' => 21
                    ]);
                return $this->jsonData(-1, '取消订单已申请，即时联系商家～');
            }
        } else {
            return $this->jsonData(-1, 'ERR user close');
        }
    }

    //确认收货
    public function success(Request $request)
    {
        $order_id = $request->input('order_id', '');
        $user_id = $request->get('jwt')->id;
        $orderInfo = DB::table('order')
            ->where([
                ['order_id', '=', $order_id],
                ['user_id', '=', $user_id],
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
}
