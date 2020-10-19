<?php

namespace App\Http\Middleware;

use Closure;

class ResponseMiddleware
{

    private $error_code = [
        -1000 => '用户不存在'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        $response = $next($request);
        // $original = $response->original;
        return response($response->original);
        // $code = 0;
        // $message = '';
        // $data = $original;

        // // if (gettype($original) == 'integer') {
        // //     $code = $original;
        // //     $message = $this->error_code[$original];
        // // }
        // // dump($original, gettype($original));
        // echo $data;
        // die;
        // // die;

        // return
        //     response()->json([
        //         "code" => $code,
        //         "message" => gettype($original),
        //         // "data" => $data
        //     ]);

        // return $response;
    }
}
