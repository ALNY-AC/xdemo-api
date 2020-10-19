<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $Users =  DB::table('users');
        $user =   $Users
            ->where('account', $request->input('account'))
            ->first();

        if (!$user) {
            return response()->json([
                "code" => -1,
                "message" => '无效的用户名',
                "data" => 1
            ]);
        }

        if ($user->password == md5(env('APP_KEY') . $request->input('password'))) {


            $jwt = encrypt(json_encode($user));

            return response()->json([
                "code" => 1,
                "message" => '登陆成功',
                "data" => $user,
                "jwt" => $jwt,
            ]);
        } else {
            return response()->json([
                "code" => -2,
                "message" => '密码不正确',
                "data" => 1
            ]);
        }
    }

    public function info(Request $request)
    {
        $all = $request->all();

        return response()->json([
            "code" => 1,
            "message" => 'success',
            "data" => $all
        ]);
    }
}
