<?php

namespace App\Http\Controllers\H5;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WaterCouponController extends Controller
{
    use ResponseJson;

    //用户水票
    public function user_list(Request $request)
    {

        $user_id = $request->get('jwt')->id;
        $DB = DB::table('user_water')
            ->select('user_water.*', 'stores.name as store_name')
            ->leftJoin('stores', 'user_water.store_id', '=', 'stores.id')
            ->where([
                ['user_water.user_id', '=', $user_id],
                ['user_water.data_state', '=', 1],
                ['user_water.last_num', '>', 0],
            ])
            ->orderBy('user_water.add_time', 'desc'); //排序

        $total = $DB->count() + 0;
        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10))
                ->limit($request->input('page_size', 10));
        }
        $result = $DB->get();

        // $result->map(function ($item, $key) use ($result) {

        //     // if ($item->last_num <= 0) {
        //     //     unset($result[$key]);
        //     // }
        //     // $item->goods = DB::table('goods')
        //     //     ->whereIn('id', json_decode($item->goods_id, true))
        //     //     ->get();

        //     return $item;
        // });

        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData($result->count(), 'OK', $data);
    }


    //用户历史水票
    public function user_list_history(Request $request)
    {

        $user_id = $request->get('jwt')->id;
        $DB = DB::table('user_water')
            ->select('user_water.*', 'stores.name as store_name')
            ->leftJoin('stores', 'user_water.store_id', '=', 'stores.id')
            ->where([
                ['user_water.user_id', '=', $user_id],
                ['user_water.data_state', '=', 1],
                ['user_water.last_num', '<=', 0],
            ])
            ->orderBy('user_water.add_time', 'desc'); //排序

        $total = $DB->count() + 0;
        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10))
                ->limit($request->input('page_size', 10));
        }
        $result = $DB->get();

        // $result->map(function ($item, $key) use ($result) {

        //     if ($item->last_num <= 0) {
        //         unset($result[$key]);
        //     }
        //     $item->goods = DB::table('goods')
        //         ->whereIn('id', json_decode($item->goods_id, true))
        //         ->get();

        //     return $item;
        // });

        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData($result->count(), 'OK', $data);
    }


    // 


    public function list(Request $request)
    {
        $store_id = $request->input('store_id');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }
        $DB = DB::table('water_coupon')
            ->where('data_state', 1)
            ->where('store_id', $store_id)
            ->orderBy('add_time', 'desc'); //排序

        $total = $DB->count() + 0;
        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10))
                ->limit($request->input('page_size', 10));
        }
        $result = $DB->get();

        $result->map(function ($item) {
            $item->goods = DB::table('goods')
                ->whereIn('id', json_decode($item->goods_id, true))
                ->get();

            return $item;
        });

        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData($result->count(), 'OK', $data);
    }

    //商家水票信息
    public function info(Request $request)
    {
        $user_id = $request->get('jwt')->id;
        $result = DB::table('water_coupon')
            ->where('id', $request->input('id'))
            ->where('data_state', 1)
            ->first();
        if (!$result) {
            return $this->jsonData(-1, 'ERR');
        }

        $res = DB::table('user_store')
            ->where([
                ['user_id', '=', $user_id],
                ['store_id', '=', $result->store_id],
            ])
            ->first();
        if (!$res) {
            DB::table('user_store')
                ->insert([
                    'user_id' => $user_id,
                    'store_id' => $result->store_id,
                ]);
        }

        $result->goods_id = json_decode($result->goods_id, true);

        return $this->jsonData(1, 'OK', $result);
    }

    //用户水票
    public function user_info(Request $request)
    {
        $water_id = $request->input('id');
        $user_id = $request->get('jwt')->id;
        $result = DB::table('user_water')
            ->where([
                ['id', '=', $water_id],
                ['user_id', '=', $user_id],
                ['data_state', '=', 1],
            ])
            ->first();
        if (!$result) {
            return $this->jsonData(-1, 'ERR');
        }
        $result->goods_id = json_decode($result->goods_id, true);

        return $this->jsonData(1, 'OK', $result);
    }

    //购买水票
    public function buyWaterCoupon(Request $request)
    {
        $user_id = $request->get('jwt')->id;
        $water_id = $request->input('water_id');
        $buy_num = $request->input('buy_num');
        $remarks = $request->input('remarks', '');
        if (!$water_id || !$buy_num) {
            return $this->jsonData(-1, '参数ERR');
        }
        $waterInfo = DB::table('water_coupon')
            ->select('id', 'min', 'name', 'price', 'stock', 'goods_id', 'store_id')
            ->where('id', $water_id)
            ->first();
        if (!$waterInfo) {
            return $this->jsonData(-1, 'ERR');
        }

        //校验最低购买数量
        //        if ($waterInfo->stock != -1) {
        //            if ($waterInfo->stock - $buy_num < 0) {
        //                return $this->jsonData(-1, '库存不足');
        //            }
        //        }
        if ($waterInfo->min > $buy_num) {
            return $this->jsonData(-1, '低于最低购买数量');
        }
        $waterInfo->buy_num = $buy_num;

        //水票抽成
        $storeInfo = DB::table('store_profile')
            ->where('store_id', $waterInfo->store_id)
            ->first();
        if (!$storeInfo) {
            return $this->jsonData(-1, 'err');
        }
        //创建订单
        $orderInfo = [];
        $orderInfo['order_id'] = 'ORDER' . Carbon::now()->format('YmdHis') . rand(10000, 99999);
        $orderInfo['pay_id'] = 'PAY' . Carbon::now()->format('YmdHis') . rand(10000, 99999);
        $orderInfo['user_id'] = $user_id;
        $orderInfo['store_id'] = $waterInfo->store_id;
        $orderInfo['price'] = $waterInfo->price * $buy_num;
        $orderInfo['re_price'] = $waterInfo->price * $buy_num;
        $orderInfo['type'] = 'water_coupon';
        $orderInfo['remarks'] = $remarks;
        $orderInfo['coupon_num'] = $buy_num;
        $orderInfo['order_royalty'] = $storeInfo->order_royalty;
        $orderInfo['total_royalty'] = $buy_num * $storeInfo->order_royalty;

        $payInfo = [];
        $payInfo['pay_id'] = $orderInfo['pay_id'];
        $payInfo['price'] = $orderInfo['price'];
        $payInfo['type'] = $orderInfo['type'];
        $payInfo['store_id'] = $orderInfo['store_id'];
        $payInfo['state'] = 0;

        $snapshotInfo = [];
        $snapshotInfo['order_id'] = $orderInfo['order_id'];
        $snapshotInfo['user_id'] = $user_id;
        $snapshotInfo['store_id'] = $orderInfo['store_id'];
        $snapshotInfo['type'] = $orderInfo['type'];
        $snapshotInfo['title'] = $waterInfo->name;
        $snapshotInfo['water_id'] = $water_id;
        $snapshotInfo['data'] = json_encode($waterInfo);

        DB::beginTransaction();
        $order_res = DB::table('order')
            ->insert($orderInfo);
        $pay_res = DB::table('pay')
            ->insert($payInfo);
        $sn_res = DB::table('snapshot')
            ->insert($snapshotInfo);
        //        DB::table('water_coupon')
        //            ->where('id', $water_id)
        //            ->decrement('stock', $buy_num);

        if ($order_res && $pay_res && $sn_res) {
            DB::commit();
            return $this->jsonData(1, 'OK', ['order_id' => $orderInfo['order_id'], 'pay_id' => $orderInfo['pay_id']]);
        } else {
            DB::rollback();
            return $this->jsonData(-1, 'ERR');
        }
    }
}
