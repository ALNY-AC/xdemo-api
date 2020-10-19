<?php

namespace App\Http\Controllers\Mini;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Model\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Storage;

class StoreController extends Controller
{
    use ResponseJson;

    public function save(Request $request)
    {

        if ($request->filled('id')) {

            $result = Store::where('id', $request->input('id'))
                ->update($request->all());

            if ($result >= 0) {
                return $this->jsonData(1, '保存成功', $request->input('id'));
            } else {
                return $this->jsonData(-1, '保存失败', $result);
            }
        } else {
            $store = new Store($request->all());
            $store->user_id = $request->get('jwt')->id;
            $result = $store->save();
            $store_id = $store->id;

            DB::table('store_profile')
                ->insert([
                    'store_id' => $store_id,
                    'money_royalty' => 0.006,
                ]);

            DB::table('admin_store')
                ->insert([
                    'store_id' => $store_id,
                    'user_id' => $request->get('jwt')->id,
                ]);

            if ($result) {
                return $this->jsonData(1, '保存成功', $store_id);
            } else {
                return $this->jsonData(-1, '保存失败', $store_id);
            }
        }
    }

    public function info(Request $request)
    {
        $store_id = $request->input('id', '');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }
        $store = DB::table('stores')
            ->where('id', $store_id)
            ->first();

        return $this->jsonData(1, 'Ok', $store);
    }

    public function moneyInfo(Request $request)
    {
        $store_id = $request->input('id', '');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }
        $store = DB::table('store_profile')
            ->where('store_id', $store_id)
            ->first();

        return $this->jsonData(1, 'Ok', $store);
    }

    public function list(Request $request)
    {
        $user_id = $request->get('jwt')->id;
        $result = DB::table('admin_store')
            ->leftJoin('stores', 'admin_store.store_id', '=', 'stores.id')
            ->where([
                ['admin_store.user_id', '=', $user_id],
                ['admin_store.data_state', '=', 1],
            ])
            ->orderBy('admin_store.add_time', 'ASC')
            ->get();
        return $this->jsonData(1, 'success', ['list' => $result, "total" => 0]);
    }

    public function data(Request $request)
    {
        $store_id = $request->input('store_id', '');
        $time = $request->input('times', '');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }

        if ($time === 'this_week') {
            //本周
            $time = [Carbon::today()->startOfWeek(), Carbon::now()->endOfWeek()];
        } elseif ($time === 'this_month') {
            //本月
            $time = [Carbon::today()->firstOfMonth(), Carbon::today()->endOfMonth()];
        } else {
            $time = [Carbon::today(), Carbon::today()->addDay()];
        }
        //订单收入
        $data['order_money'] = DB::table('order')
            ->where([
                ['store_id', '=', $store_id],
                ['state', '=', 4],
                ['type', '=', 'pay_order'],
            ])
            ->whereBetween('edit_time', $time)
            ->sum('re_price');
        //水票收入
        $data['coupon_money'] = DB::table('order')
            ->where([
                ['store_id', '=', $store_id],
                ['state', '=', 4],
                ['type', '=', 'water_coupon'],
            ])
            ->whereBetween('edit_time', $time)
            ->sum('re_price');
        //待处理订单
        $data['wait_count'] = DB::table('order')
            ->where([
                ['store_id', '=', $store_id],
                ['state', '=', 1],
                ['type', '!=', 'water_coupon'],
            ])
            ->whereBetween('edit_time', $time)
            ->count();
        //今日完成订单
        $data['finish_count'] = DB::table('order')
            ->where([
                ['store_id', '=', $store_id],
                ['state', '=', 4],
                ['type', '!=', 'water_coupon'],
            ])
            ->whereBetween('edit_time', $time)
            ->count();
        //退款订单
        $data['refund_count'] = DB::table('pay')
            ->where([
                ['store_id', '=', $store_id],
                ['state', '=', 4],
                ['type', '!=', 'water_coupon'],
            ])
            ->whereBetween('edit_time', $time)
            ->count();
        //新增用户
        $data['add_users'] = DB::table('user_store')
            ->where('store_id', $store_id)
            ->whereBetween('edit_time', $time)
            ->count();

        return $this->jsonData(1, 'OK', $data);
    }

    public function qrCode(Request $request)
    {
        $store_id = $request->input('store_id');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }
        $store = DB::table('stores')
            ->leftJoin('store_profile', 'stores.id', '=', 'store_profile.store_id')
            ->where('stores.id', $store_id)
            ->first();
        if (!$store) {
            return $this->jsonData(-1, 'ERR');
        }
        if ($store->wx_store_img) {
            return $this->jsonData(1, 'OK', ['img' => 'https://api.h2o.cy-cube.com' . $store->wx_store_img]);
        }

        $config = [
            'app_id' => env('WX_WECHAT_APP_ID'),
            'secret' => env('WX_WECHAT_SECRET'),
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        $data = ['store_id' => $store_id];
//        $result = $app->qrcode->temporary(json_encode($data), 6 * 24 * 3600);
        $result = $app->qrcode->forever(json_encode($data));
        $url = $app->qrcode->url($result['ticket']);
        $content = file_get_contents($url);
        $path = '/public/files/' . date('Ymd', time()) . '/' . date('Ymdhis', time()) . rand(1000, 9999) . '.jpg';
        $res = Storage::put($path, $content);
        if ($res) {
            DB::table('store_profile')
                ->where('store_id', $store_id)
                ->update([
                    'wx_store_img' => $path
                ]);

            $data = [];
            $data['url'] = $path;
            DB::table('file')->insert($data);
        }
        return $this->jsonData(1, 'OK', ['img' => 'https://api.h2o.cy-cube.com' . $path]);
    }

    //
    //
    public function user(Request $request)
    {
        $store_id = $request->input('store_id', '');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }
        $DB = DB::table('user_store')
            ->leftJoin('users', 'user_store.user_id', '=', 'users.id')
            ->where('user_store.store_id', $store_id)
            ->orderBy('user_store.add_time', 'desc');;

        $total = $DB->count() + 0;
        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10))
                ->limit($request->input('page_size', 10));
        }
        $result = $DB->get();
        $data = [
            "list" => $result,
            "total" => $total,
        ];
        return $this->jsonData($result->count(), 'OK', $data);
    }
}
