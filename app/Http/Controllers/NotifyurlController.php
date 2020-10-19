<?php

namespace App\Http\Controllers;

use App\Http\Response\ResponseJson;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\News;
use EasyWeChat\Kernel\Messages\NewsItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyurlController extends Controller
{

    use ResponseJson;

    public function wxNotifyUrl()
    {
        $config = [
            'app_id' => env('WX_WECHAT_APP_ID'),
            'secret' => env('WX_WECHAT_SECRET'),
            'token' => 'B89E91CCF14BFD7F485DD7BE7D789B0A',          // Token
            'aes_key' => 'h88vBJ0UcvrGht3gmbiddOv5tomoFOpmRVAQPmulEii',    // EncodingAESKey，兼容与安全模式下请一定要填写！！！
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);

        $app->server->push(function ($message) {
            Log::info(json_encode($message, JSON_UNESCAPED_UNICODE));
            switch ($message['MsgType']) {
                case 'event':

                    if ($message['Event'] === 'SCAN') {
                        $data = json_decode($message['EventKey'], true);
                    } elseif ($message['Event'] === 'subscribe') {
                        $data = json_decode(substr($message['EventKey'], 8), true);
                    }
                    $store_id = $data['store_id'];
                    //获取信息
                    $storeInfo = DB::table('stores')
                        ->where('id', $store_id)
                        ->first();

                    if ($storeInfo->is_up != 1) {
                        break;
                    }

                    $items = [
                        new NewsItem([
                            'title' => '点我立即订水',
                            'description' => $storeInfo->name,
                            'url' => 'https://h5.h2o.cy-cube.com/store/info?store_id=' . $store_id,
                            'image' => 'https://api.h2o.cy-cube.com/logo.png',
                        ]),
                    ];
                    $news = new News($items);
                    return $news;
                    break;
            }
        });
        $response = $app->server->serve();
        $response->send();
        echo $response;
    }
}

/**
 * {
 * "ToUserName": "gh_3c6c7970976a",
 * "FromUserName": "oTIHrwTJdWuhBM_uhFBUFnfrKxA0",
 * "CreateTime": "1587807483",
 * "MsgType": "event",
 * "Event": "subscribe",
 * "EventKey": "qrscene_{\"store_id\":28}",
 * "Ticket": "gQER8TwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAydzhfbkI2YzFmSDExTElFSGh1Y0IAAgTs-6NeAwQA6QcA"
 * }
 */