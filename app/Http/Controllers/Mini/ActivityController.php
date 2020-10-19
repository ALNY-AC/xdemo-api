<?php

namespace App\Http\Controllers\Mini;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{

    use ResponseJson;

    public function order(Request $request)
    {
        $time = $request->input('time');
        $distance = $request->input('distance');
        $user_id = $request->get('jwt')->id;
        $userInfo = DB::table('users')
            ->where('id', $user_id)
            ->first();
        if (!$time) {
            $price = 0.01;
        }
        $price = ($distance * 0.01) * ($time * 0.01);
        if ($price < 0.01) {
            $price = 0.01;
        }

        $config = [
            // 必要配置
            'app_id' => env('WX_MINI_APP_ID'),
            'mch_id' => env('WX_PAY_MCH_ID'),
            'key' => env('WX_PAY_KEY'),   // API 密钥
            'notify_url' => url("api/mini/activity/wx_notify_url"),     // 你也可以在下单时单独设置来想覆盖它
        ];

        $app = Factory::payment($config);
        $jssdk = $app->jssdk;

        $result = $app->order->unify([
            'body' => '云水',
            'out_trade_no' => 'ORDER' . Carbon::now()->format('YmdHis') . rand(10000, 99999),
            'total_fee' => $price * 100,
            'trade_type' => 'JSAPI', // 请对应换成你的支付方式对应的值类型
            'openid' => $userInfo->openid,
        ]);
        if ($result['return_code'] != 'SUCCESS') {
            return [
                'code' => -1,
                'data' => $result,
                'msg' => 'error',
            ];
        }
        $config = $jssdk->sdkConfig($result['prepay_id']); // 返回数组
        return $this->jsonData(1, 'OK', $config);

    }

    public function wx_notify_url()
    {
        $config = [
            'app_id' => env('WX_MINI_APP_ID'),
            'mch_id' => env('WX_PAY_MCH_ID'),
            'key' => env('WX_PAY_KEY'),
        ];

        $wx = Factory::payment($config);
        $response = $wx->handleRefundedNotify(function ($message, $fail) {
            Log::info('小程序支付：' . json_encode($message, JSON_UNESCAPED_UNICODE));
//            if ($message['return_code'] === 'SUCCESS') {
//                if ($reqInfo['refund_status'] === 'SUCCESS') {
//                    $pay = DB::table('pay')
//                        ->where('pay_id', $reqInfo['out_trade_no'])
//                        ->first();
//
//                    if (!$pay) {
//                        Log::info('支付单不存在：' . json_encode($pay, JSON_UNESCAPED_UNICODE));
//                        return true;
//                    }
//
//                    DB::table('pay')
//                        ->where('pay_id', $reqInfo['out_trade_no'])
//                        ->update([
//                            'state' => 4
//                        ]);
//                    return true;
//                }
//            }
//            Log::info(json_encode($reqInfo, JSON_UNESCAPED_UNICODE));
//            Log::info(json_encode($fail, JSON_UNESCAPED_UNICODE));

            return true;
        });
        echo $response;
    }
}

//message
/**
 * {
 * "return_code": "SUCCESS",
 * "appid": "wxb21c49c1f4205110",
 * "mch_id": "1573115611",
 * "nonce_str": "bdf57bf6a8c28524cc0e73b7b90d8ecb",
 * "req_info": "RNCuBT0D5VxXiWwg/mLfbMKqRTc2XzDuF8sHMWfee4eK1TUpCuJYujTpN7VOyBLfvdb2SBoKpEyXL5HqpRj8J/c5o2W/Nsev0H3JgVVwakwHYpx5UdN/EPqzCnN0JCtyMQioVoaxscJNMBZefTsy5lwGZkh2I8ZUiEmTXUVAPBE36KLRKMczP/E/HmK/nZIBn5zIlRw61Wc3IeLAsb7wIrVNYC7N2zkdnwbI3PF+KihvccLBVYYl0dhYfRW0m6bR7nvuwWa9E516JdBkJOXlsIIlC6L9Xg3PKI8tcu7t17XpPXPQEsNUab5vpaqSP8knwmKq5pofw7vDvAicb26V0611pxCkAzwzO+q5/97xu3WVE6oBxGfuY/BUUeknA2ucLI6P4MtCpL8+DHDcnhG6iFt1lcWGQw9T9LmZXzJZpeH+PH6SxIbdpP1bmFRsL8Tk0rg+m4zV5qBumc7fgHlmt1wk8R2aejj5zUHZFZ02HcI8xtp+kJX96KB7Pn9ocaLu4n43cMDFtjrkaT/siXUoX0e3UN874e02R9E64Ew9W2CgoHFTek4TfM49XSSIbAeLh4H7As5/0WZTBW80CKHfhnRxIBwXJCiDMyZVedqSEWRda0kyodYK1oq5dmxOCRgr0EAA6Do7M5AB7QiTKQy/i7h58p48s5A8BSs+nzDtK3EwzhhZpFkT32HqyL8Rw057pSY0mYAwTuJNRb904bCJuS+toCGCBYikJMuWIvvVUrzafcjyhTSeBEekl+btOb1FOlCEs3BpjWSxn5OVd/rMY4Q6jBC9bjhJ8HlZow1cv1hof6pLGLt1FCBXWjgjoBCoCStIlpocpTEcXOkaGBmmyHR86QwFNTn4KvuyaSRPITZOLYbDopT0nq9Oz4Lz4AL3oB5SW2A/bR1Mngjmg/pd4pXqGByCebHmYwQf7OT2Gp/G8yl4RGXdTb8bbRCr4EEg7PJHAQPZO1eUhtvbd4xQhsr2OQZiUgqhLz6DdPRJ7g72S5koxdWJgV0ZrdmijafG42DnRvJFWZV8YWkUdBZtsmlGVZqsyG4ypC+kb+coZYMIU8Swjn0SdPNGAOTisnIA"
 * }
 */
//$reqInfo
/**
 * {
 * "out_refund_no": "PAY2020042218060618263",
 * "out_trade_no": "PAY2020042218060618263",
 * "refund_account": "REFUND_SOURCE_RECHARGE_FUNDS",
 * "refund_fee": "1",
 * "refund_id": "50300604072020042300225680793",
 * "refund_recv_accout": "支付用户零钱",
 * "refund_request_source": "API",
 * "refund_status": "SUCCESS",
 * "settlement_refund_fee": "1",
 * "settlement_total_fee": "1",
 * "success_time": "2020-04-23 15:21:14",
 * "total_fee": "1",
 * "transaction_id": "4200000536202004222148106478"
 * }
 */
