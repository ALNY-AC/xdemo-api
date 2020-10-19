<?php

namespace App\Http\Controllers\Mini;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use EasyWeChat\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SendMsgController extends Controller
{

    use ResponseJson;

    private $app;

    public function __construct()
    {
        $config = [
            'app_id' => env('WX_MINI_APP_ID'),
            'secret' => env('WX_MINI_APP_SECRET'),
            'response_type' => 'array',
        ];
        $this->app = Factory::miniProgram($config);
    }

    public function send($page, $open_id, $send)
    {
        $data = [
            'template_id' => 'vJY7NVaVRgldafpF54RFPxCRMGZFwfNTzJ9w0Umu6Io',//$template[$type], // 所需下发的订阅模板id
            'touser' => $open_id,     // 接收者（用户）的 openid
            'page' => $page,       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
            'data' => [
                'thing3' => [
                    'value' => $send['goods_name'],
                ],
                'date5' => [
                    'value' => $send['pay_time'],
                ],
                'thing9' => [
                    'value' => mb_substr($send['address'], 0, 15, 'utf-8') . '...',
                ],
                'name7' => [
                    'value' => $send['contacts'],
                ],
                'phone_number8' => [
                    'value' => $send['phone'],
                ],
            ],
        ];
        $req = $this->app->subscribe_message->send($data);
        Log::info('消息推送：' . json_encode($req, JSON_UNESCAPED_UNICODE));
    }

}
