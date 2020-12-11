<?php

use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// include_once("mini.php");
// include_once("admin.php");
// include_once("h5.php");
// include_once("ctos.php");
// include_once("erp.php");
include_once("vs.php");
include_once("paper.php");
include_once("shop.php");
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/', function () {
    return 'xdemo-api';
});

Route::any('/test', function (Request $request) {
    $data = [];
    $data['a'] = '1';
    $data['b'] = '1';
    $data['data'] = $request->all();
    return $data;
});



// //门店
// Route::prefix('jwt')->group(function () {
//     Route::any('get', 'JwtController@get');
// });

// //上传
// Route::prefix('file')->group(function () {
//     Route::any('upload', 'FileController@upload');
// });

// //微信回调
// Route::any('wx_notify_url', 'NotifyurlController@wxNotifyUrl');
// // ResponseMiddleware


// //配置微信菜单
// Route::any('create', function () {
//     $config = [
//         'app_id' => env('WX_WECHAT_APP_ID'),
//         'secret' => env('WX_WECHAT_SECRET'),
//         'response_type' => 'array',
//     ];
//     $app = Factory::officialAccount($config);
//     $buttons = [
//         [
//             "type" => "view",
//             "name" => "在 线 定 水",
//             "key" => "BY_WATER",
//             "url" => "https://h5.h2o.cy-cube.com/login",
//         ],
// //        [
// //            "name" => "菜单",
// //            "sub_button" => [
// //                [
// //                    "type" => "view",
// //                    "name" => "搜索",
// //                    "url" => "http://www.soso.com/"
// //                ],
// //                [
// //                    "type" => "view",
// //                    "name" => "视频",
// //                    "url" => "http://v.qq.com/"
// //                ],
// //                [
// //                    "type" => "click",
// //                    "name" => "赞一下我们",
// //                    "key" => "V1001_GOOD"
// //                ],
// //            ],
// //        ],
//     ];
// //    $response = $app->menu->delete();
//     $response = $app->menu->create($buttons);
//     \Illuminate\Support\Facades\Log::info('创建菜单：' . json_encode($response, JSON_UNESCAPED_UNICODE));
// //    return $response;
//     if ($response['errcode'] === 0) {
//         return '创建菜单成功！';
//     }
// });