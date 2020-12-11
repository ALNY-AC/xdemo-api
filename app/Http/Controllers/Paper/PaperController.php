<?php

namespace App\Http\Controllers\Paper;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use App\Listeners\Random;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PaperController extends Controller
{

    use ResponseJson;
    public function list(Request $request)
    {

        $DB = DB::table('goods') //定义表
            ->where('store_id', $request->input('store_id'))
            ->orderBy('add_time', 'desc'); //排序 

        if ($request->filled('goods_ids')) {
            $DB->whereIn('id', $request->input('goods_ids'));
        }

        if ($request->filled('class_id')) {
            $DB->where('class_id', $request->input('class_id'));
        }
        if ($request->filled('is_up')) {
            $DB->where('is_up', $request->input('is_up'));
        }
        if ($request->filled('name')) {
            $DB->where('title', 'like', '%' . $request->input('name') . '%');
        }
        $total = $DB->count() + 0;

        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10))
                ->limit($request->input('page_size', 10));
        }

        $result = $DB->get();

        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData($result->count(), 'OK', $data);
    }

    public function info(Request $request)
    {
        $result = DB::table('goods')
            ->where('id', $request->input('id'))
            ->first();

        if (!$result) {
            return $this->jsonData(-1, '操作失败');
        }
        return $this->jsonData(1, 'OK', $result);
    }
    
    // 保存配置项
    public function save(Request $request)
    {

        

        // $data = $request->toArray();
        // if (!$data['title'] || !$data['store_id'] || !$data['class_id']) {
        //     return $this->jsonData(-1, '参数错误');
        // }

        // if ($request->filled('id')) {
        //     $result = DB::table('goods')
        //         ->where('id', $request->input('id'))
        //         ->update($data);
        // } else {
        //     $result = DB::table('goods')->insert($data);
        // }

        // if (!$result && $result !== 0) {
        //     return $this->jsonData(-1, '操作失败');
        // }
        // return $this->jsonData(1, 'OK');
    }

    public function del(Request $request)
    {

        $result = DB::table('goods')
            ->where('id', $request->input('id'))
            ->delete();

        if (!$result) {
            return $this->jsonData(-1, '操作失败');
        }
        return $this->jsonData(1, 'OK');
    }
}
