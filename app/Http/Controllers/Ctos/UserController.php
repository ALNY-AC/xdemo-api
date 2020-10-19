<?php

namespace App\Http\Controllers\Ctos;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Lib\Dada\Dada;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use App\Listeners\Random;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserController extends Controller
{
    use ResponseJson;

    // 用户列表
    public function list(Request $request)
    {

        $DB = DB::table('users')//定义表
        ->orderBy('add_time', 'desc'); //排序


        if ($request->filled('user_type')) {
            $DB->where('user_type', $request->input('user_type'));
        }

        if ($request->filled('data_state')) {
            $DB->where('data_state', $request->input('data_state'));
        }

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

        return $this->jsonData($result->count(), 'success', ['list' => $result, "total" => $total]);
    }

    // 修改密码
    public function setPwd(Request $request)
    {

        $data = [];

        $data['pwd'] = md5($_ENV['APP_KEY'] . $request->input('pwd'));

        $result = DB::table('users')
            ->where('id', $request->input('id'))
            ->update($data);

        return response()->json([
            'code' => $result >= 0 ? 1 : -1,
            'msg' => $result >= 0 ? 'success' : 'error',
            'data' => $result,
        ]);
    }

    public function info(Request $request)
    {

        $id = '';
        if ($request->filled('id')) {
            $id = $request->input('id');
        } else {
            $id = $request->jwt->id;
        }

        $result = DB::table('users')
            ->where('id', $id)
            ->first();

        return response()->json([
            'code' => $result ? 1 : -1,
            'msg' => $result ? 'success' : 'error',
            'data' => $result,
            'id' => $id,
        ]);
    }

    public function save(Request $request)
    {
        if ($request->filled('id')) {
            $data = [];
            if ($request->filled('pwd')) {
                $data['pwd'] = md5($_ENV['APP_KEY'] . $request->input('pwd'));
            }
            if ($request->filled('phone')) {
                $data['phone'] = $request->input('phone');
            }
            if ($request->filled('data_state')) {
                $data['data_state'] = $request->input('data_state');
            }
            if ($request->filled('mini_qrcode_img')) {
                $data['mini_qrcode_img'] = $request->input('mini_qrcode_img');
            }

            $result = DB::table('users')
                ->where('id', $request->input('id'))
                ->update($data);

            return response()->json([
                'code' => $result >= 0 ? 1 : -1,
                'msg' => $result >= 0 ? 'success' : 'error',
                'data' => $result,
            ]);
        } else {
            // 添加
            /**检查是否重复 */
            if (DB::table('users')->where('phone', $request->input('phone'))->first()) {
                return response()->json([
                    'code' => -1,
                    'msg' => '用户已存在！',
                    'data' => null,
                ]);
            } else {
                $data = [];
                if ($request->filled('pwd')) {
                    $data['pwd'] = md5($_ENV['APP_KEY'] . $request->input('pwd'));
                }
                if ($request->filled('phone')) {
                    $data['phone'] = $request->input('phone');
                }
                if ($request->filled('data_state')) {
                    $data['data_state'] = $request->input('data_state');
                }
                if ($request->filled('mini_qrcode_img')) {
                    $data['mini_qrcode_img'] = $request->input('mini_qrcode_img');
                }

                $result = DB::table('users')->insert($data);
                return response()->json([
                    'code' => $result ? 1 : -1,
                    'msg' => $result ? 'success' : 'error',
                    'data' => $result,
                ]);
            }
        }
    }
}
