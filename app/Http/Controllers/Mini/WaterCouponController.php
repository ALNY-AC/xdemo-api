<?php

namespace App\Http\Controllers\Mini;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaterCouponController extends Controller
{
    use ResponseJson;

    public function list(Request $request)
    {

        $store_id = $request->input('store_id');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }
        $DB = DB::table('water_coupon')
            ->where([
                ['store_id', '=', $store_id],
                ['data_state', '=', 1],
            ])
            ->orderBy('add_time', 'desc');

        $total = $DB->count() + 0;
        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10))
                ->limit($request->input('page_size', 10));
        }
        $result = $DB->get();
        $result->map(function ($item) {
            $waterInfo = DB::table('snapshot')
                ->leftJoin('order', 'snapshot.order_id', '=', 'order.order_id')
                ->leftJoin('pay', 'order.pay_id', '=', 'pay.pay_id')
                ->where([
                    ['water_id', '=', $item->id],
                    ['pay.state', '=', 2],
                    ['pay.type', '=', 'water_coupon'],
                ])
                ->sum('order.coupon_num');
            $item->coupon_num = $waterInfo;
            return $item;
        });

        $data = [
            "list" => $result,
            "total" => $total,
        ];
        return $this->jsonData($result->count(), 'OK', $data);
    }

    public function info(Request $request)
    {
        $water_id = $request->input('id');
        $result = DB::table('water_coupon')
            ->where('id', $water_id)
            ->where('data_state', 1)
            ->first();
        if (!$result) {
            return $this->jsonData(-1, 'ERR', $result);
        }
        $result->goods_id = json_decode($result->goods_id);
        $coupon_num = DB::table('snapshot')
            ->leftJoin('order', 'snapshot.order_id', '=', 'order.order_id')
            ->leftJoin('pay', 'order.pay_id', '=', 'pay.pay_id')
            ->where([
                ['water_id', '=', $water_id],
                ['pay.state', '=', 2],
                ['pay.type', '=', 'water_coupon'],
            ])
            ->sum('order.coupon_num');

        $coupon_money = DB::table('snapshot')
            ->leftJoin('order', 'snapshot.order_id', '=', 'order.order_id')
            ->leftJoin('pay', 'order.pay_id', '=', 'pay.pay_id')
            ->where([
                ['water_id', '=', $water_id],
                ['pay.state', '=', 2],
                ['pay.type', '=', 'water_coupon'],
            ])
            ->sum('pay.price');
        $result->coupon_num = $coupon_num;
        $result->coupon_money = $coupon_money;

        return $this->jsonData(1, 'OK', $result);
    }

    public function save(Request $request)
    {
        $data = [];
        $data['store_id'] = $request->input('store_id', '');
        $data['name'] = $request->input('name', '');
        $data['stock'] = $request->input('stock', 0);
        $data['price'] = $request->input('price', 0);
        $data['min'] = $request->input('min', 1);
        $data['goods_id'] = json_encode($request->input('goods_id', []));
        if ($request->filled('id')) {
            $result = DB::table('water_coupon')
                ->where('id', $request->input('id'))
                ->update($data);
        } else {
            $result = DB::table('water_coupon')->insert($data);
        }

        if (!$result) {
            return $this->jsonData(-1, 'ERR');
        }
        return $this->jsonData(1, 'OK');
    }

    public function del(Request $request)
    {
        $store_id = $request->input('store_id');
        $result = DB::table('water_coupon')
            ->where([
                ['id', '=', $request->input('id')],
                ['store_id', '=', $store_id],
            ])
            ->update([
                'data_state' => -1
            ]);
        if (!$result) {
            return $this->jsonData(-1, 'ERR');
        }
        return $this->jsonData(1, 'OK');
    }

    //
    public function user_water_coupon(Request $request)
    {
        $water_id = $request->input('water_coupon_id', '');
        if (!$water_id) {
            return $this->jsonData(-1, 'ERR water_coupon_id');
        }
        $DB = DB::table('snapshot')
            ->select('users.name', 'users.head', 'order.add_time', 'order.price', 'snapshot.data')
            ->leftJoin('order', 'snapshot.order_id', '=', 'order.order_id')
            ->leftJoin('users', 'snapshot.user_id', '=', 'users.id')
            ->where([
                ['water_id', '=', $water_id],
                ['order.type', '=', 'water_coupon'],
                ['order.state', '=', 4],
            ])
            ->orderBy('order.add_time', 'desc');

        $total = $DB->count() + 0;

        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10))
                ->limit($request->input('page_size', 10));
        }

        $result = $DB->get();
        $result->map(function ($item) {
            $item->data = json_decode($item->data);
            return $item;
        });

        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData($result->count(), 'OK', $data);
    }
}
