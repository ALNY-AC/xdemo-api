<?php

namespace App\Http\Controllers\Mini;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ClassiController extends Controller
{  // @todo AuthController 这里是要生成的类名字

    use ResponseJson;

    public function list(Request $request)
    {
        $DB = DB::table('class')
            ->where('store_id', $request->input('store_id'))
            ->where('data_state', 1)
            ->orderBy('sort', 'desc');

        $result = $DB->get();

        $data['list'] = $result;
        $data['total'] = $result->count();
        return $this->jsonData(1, 'OK', $data);
    }

    public function info(Request $request)
    {
        $result = DB::table('class')//定义表
        ->where('id', $request->input('id'))//前台传过来的id
        ->first(); //获取数据

        return $this->jsonData(1, 'OK', $result);
    }

    // 保存或者新增
    public function save(Request $request)
    {
        $data = $request->toArray();
        if (!$data['name'] || !$data['store_id']) {
            return $this->jsonData(-1, '参数错误');
        }

        if ($request->filled('id')) {
            $result = DB::table('class')
                ->where('id', $request->input('id'))
                ->update($data);
        } else {
            $result = DB::table('class')->insert($data);
        }
        return $this->jsonData(1, 'OK', $result);
    }

    public function del(Request $request)
    {
        $result = DB::table('class')//定义表
        ->where('id', $request->input('id'))//前台传过来的id
        ->delete();

        if(!$result){
            return $this->jsonData(-1, 'ERR');
        }
        return $this->jsonData(1, 'OK', $result);
    }
}
