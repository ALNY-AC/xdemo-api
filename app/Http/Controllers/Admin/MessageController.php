<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{

    public function save(Request $request)
    {

        $message = new Message($request->all());
        $result = $message->save();
        if ($result) {
            return response()->json([
                "code" => 1,
                "msg" => "创建成功",
                "data" => $result
            ]);
        } else {
            return response()->json([
                "code" => -1,
                "msg" => "创建失败",
                "data" => $result
            ]);
        }
    }
    public function list(Request $request)
    {
        $result = Message::get();
        return response()->json([
            "code" => $result->count(),
            "msg" => "获取成功",
            "data" => $result
        ]);
    }

    public function info(Request $request)
    {
        return 1;
    }
    public function del(Request $request)
    {
        return 1;
    }
}
