<?php

namespace App\Http\Controllers\H5;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use App\Listeners\Random;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class GoodsController extends Controller
{
    use ResponseJson;

    // 商品列表
    public function list(Request $request)
    {

        $DB = DB::table('goods') //定义表
            ->where('is_up', 1)
            ->orderBy('add_time', 'desc'); //排序 

        if ($request->filled('class_id')) {
            $DB->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('goods_ids')) {
            $DB->whereIn('id', $request->input('goods_ids'));
        }

        if ($request->filled('store_id')) {
            $DB->where('store_id', $request->input('store_id'));
        }

        if ($request->filled('name')) {
            $DB->where('title', 'like', '%' . $request->input('name') . '%');
        }

        $total = $DB->count() + 0;
        $result = $DB->get();

        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData(1, 'OK', $data);
    }

    // 商品详情
    public function info(Request $request)
    {

        $result = DB::table('goods')
            ->where('id', $request->input('id'))
            ->first();

        return [
            'code' => $result ? 1 : -1,
            'msg' => $result ? 'success' : 'error',
            'data' => $result,
        ];
    }
}
