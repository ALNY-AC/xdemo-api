<?php

namespace App\Http\Controllers\H5;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Model\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    use ResponseJson;

    public function list(Request $request)
    {
        $x = $request->input('x', 0);
        $y = $request->input('y', 0);
        if ($x == '' || $y == '') {
            $x = 0;
            $y = 0;
        }
        $user_x = $x;
        $user_y = $y;
        $DB = DB::table('stores');
        $DB = $DB->select(
            $DB->raw("ROUND(6378.138 * 2 * ASIN(SQRT(POW( SIN( ( $user_x * PI( ) / 180 - x * PI( ) / 180 ) / 2 ), 2 ) + COS( $user_x * PI( ) / 180 ) * COS( x * PI( ) / 180 ) * POW( SIN( ( $user_y * PI( ) / 180 - y * PI( ) / 180 ) / 2 ), 2 ))) * 1000) AS distance"),
            'id',
            'name',
            'logo',
            'label'
        )
            ->where('is_up', 1)
            ->orderBy('distance', 'ASC');

        if ($request->filled('name')) {
            $DB->where('name', 'like', '%' . $request->input('name') . '%');
        }

        $total = $DB->get()->count();
        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10));
        }
        if ($request->filled('page_size')) {
            $DB->limit($request->input('page_size', 10));
        }

        $result = $DB->get();

        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData($result->count(), 'OK', $data);
    }

    // 门店详情
    public function info(Request $request)
    {
        $id = $request->input('id');
        $result = Store::where('id', $id)
            ->first();

        if (!$result) {
            return $this->jsonData(-1, 'ERR');
        }

        $user_id = $request->get('jwt')->id;

        $res = DB::table('user_store')
            ->where([
                ['user_id', '=', $user_id],
                ['store_id', '=', $id],
            ])
            ->first();
        if (!$res) {
            DB::table('user_store')
                ->insert([
                    'user_id' => $user_id,
                    'store_id' => $id,
                ]);
        }
        DB::table('store_profile')
            ->where('store_id', $id)
            ->increment('volume');

        if (!$result) {
            return $this->jsonData(-1, '门店不存在');
        }

        return $this->jsonData(1, 'success', $result);
    }

    //收藏店铺
    public function store_star(Request $request)
    {
        $user_id = $request->jwt->id;
        $store_id = $request->input('store_id');
        $storeInfo = DB::table('store_star')
            ->where([
                ['user_id', '=', $user_id],
                ['store_id', '=', $store_id],
            ])
            ->first();
        if ($storeInfo) {
            $result = DB::table('store_star')
                ->where([
                    ['user_id', '=', $user_id],
                    ['store_id', '=', $store_id],
                ])
                ->delete();
        } else {
            $result = DB::table('store_star')
                ->insert([
                    'user_id' => $user_id,
                    'store_id' => $store_id,
                ]);
        }
        return [
            'code' => $result ? 1 : -1,
            'msg' => $result ? 'success' : 'error',
            'data' => $result,
        ];
    }

    //收藏列表
    public function star_list(Request $request)
    {
        $user_id = $request->jwt->id;
        $x = $request->input('x', '0');
        $y = $request->input('y', '0');
        $DB = DB::table('store_star')
            ->select('user_id', 'store_id')
            ->where([
                ['user_id', '=', $user_id],
                ['data_state', '=', 1],
            ])
            ->orderBy('add_time', 'desc');

        $total = $DB->count();
        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10));
        }
        if ($request->filled('page_size')) {
            $DB->limit($request->input('page_size', 10));
        }
        $result = $DB->get();
        $data = $result->map(function ($item) use ($x, $y) {
            $DB = DB::table('store');
            $DB = $DB->select(
                $DB->raw("ROUND(6378.138 * 2 * ASIN(SQRT(POW( SIN( ( $x * PI( ) / 180 - x * PI( ) / 180 ) / 2 ), 2 ) + COS( $x * PI( ) / 180 ) * COS( x * PI( ) / 180 ) * POW( SIN( ( $y * PI( ) / 180 - y * PI( ) / 180 ) / 2 ), 2 ))) * 1000) AS distance"),
                'store_id',
                'name',
                'logo',
                'info',
                'label'
            )
                ->where('store_id', $item->store_id);
            $data = $DB->first();
            if ($data->label == '') {
                $data->label = [];
            } else {
                $data->label = json_decode($data->label);
            }
            $data->star = $data->star + 0;

            //营业状态
            $data->is_open = self::checkOpen($data->week, $data->start_time, $data->end_time);

            //店铺分类
            $store_class_info = DB::table('store_class')
                ->where('id', $data->store_class_id)
                ->first();
            $data->store_class = isset($store_class_info) ? $store_class_info->name : '';

            //门店列表的三个默认商品
            $data->store_goods = DB::table('goods')
                ->select('id', 'title', 'goods_head_list', 'o_price', 'price')
                ->where([
                    ['store_id', '=', $data->store_id],
                    ['is_up', '=', 1],
                ])
                ->orderBy('sort', 'desc')
                ->limit(3)
                ->get();
            $data->store_goods->goods_head_list = $data->store_goods->map(function ($ite) {
                $ite->goods_head_list = json_decode($ite->goods_head_list);
                return $ite;
            });

            return $data;
        });
        $data->is_store_star = 1;
        return [
            'code' => $data->count(),
            'msg' => $data ? 'success' : 'error',
            'data' => $data,
            'total' => $total,
        ];
    }
}
