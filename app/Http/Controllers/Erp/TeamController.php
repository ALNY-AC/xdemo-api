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

class TeamController extends Controller
{

    use ResponseJson;

    public function list(Request $request)
    {

        $DB = DB::table('users') //定义表
            ->where('link_id', $request->get('jwt')->id)
            ->where('user_type', 3)
            ->orderBy('add_time', 'desc'); //排序

        if ($request->filled('phone')) {
            $DB->where('phone', 'like', '%' . $request->input('phone') . '%');
        }

        $total = $DB->count() + 0;

        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10));
        }

        if ($request->filled('page_size')) {
            $DB->limit($request->input('page_size', 10));
        }

        $result = $DB->get();

        return $this->jsonData($result->count(), 'success', ['total' => $total, 'list' => $result]);
    }

    public function info(Request $request)
    {

        return $this->jsonData(1, 'OK', $result);
    }
}
