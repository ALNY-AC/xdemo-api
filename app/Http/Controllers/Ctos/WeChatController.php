<?php

namespace App\Http\Controllers\Ctos;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Model\Feedback;
use Illuminate\Http\Request;
use EasyWeChat\Factory;

class WeChatController extends Controller
{
    use ResponseJson;
    public function public_menu_list(Request $request)
    {

        $config = [
            'app_id' => env('WX_WECHAT_APP_ID'),
            'secret' => env('WX_WECHAT_SECRET'),
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);

        $current = $app->menu->current();

        return $this->jsonData(1, 'success', $current);
    }

    public function public_menu_save(Request $request)
    {
        $config = [
            'app_id' => env('WX_WECHAT_APP_ID'),
            'secret' => env('WX_WECHAT_SECRET'),
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);

        $buttons = $request->all();
        $response = $app->menu->delete();
        $response = $app->menu->create($buttons);
        \Illuminate\Support\Facades\Log::info('创建菜单：' . json_encode($response, JSON_UNESCAPED_UNICODE));
        return $response;
    }
}
