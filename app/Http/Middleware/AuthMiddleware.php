<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class AuthMiddleware
{

    public function handle(Request $request, \Closure $next)
    {

        $jwt = $request->get('jwt');

        if ($jwt && $jwt != 'undefined') {
            if ($this->checkUser($jwt)) {
                return $next($request);
            } else {
                return response()->json([
                    'code' => -401,
                    'msg' =>  '未登录',
                    'data' => null,
                ], 401);
            }
        } else {
            return response()->json([
                'code' => -401,
                'msg' =>  '未登录',
                'data' => null,
            ], 401);
        }
    }
    private function checkUser($jwt)
    {

        $DB = DB::table('users');
        $is = $DB->where('id', $jwt->id)->exists();

        return $is;
    }
}
