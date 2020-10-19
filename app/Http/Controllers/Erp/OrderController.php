<?php

namespace App\Http\Controllers\Erp;
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

        $DB = DB::table('order')
            ->where('type', 'pay_order')
            ->orderBy('add_time', 'desc');

        if ($request->filled('store_id')) {
            $DB->where('store_id', $request->input('store_id'));
        }

        if ($request->filled('state')) {
            $DB->where('state', $request->input('state'));
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
            $item->payInfo = DB::table('pay')->where('pay_id', $item->pay_id)->get();
            $item->storeInfo = DB::table('stores')->where('id', $item->store_id)->first();
            $item->addressInfo = DB::table('order_address')->where('id', $item->address_id)->first();
            $item->userInfo = DB::table('users')->where('id', $item->user_id)->first();
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
}
