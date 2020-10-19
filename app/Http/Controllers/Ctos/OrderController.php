<?php

namespace App\Http\Controllers\Ctos;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use EasyWeChat\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{

    use ResponseJson;

    public function list(Request $request)
    {

        $DB = DB::table('order')
            ->where('type', 'pay_order')
            ->orderBy('add_time', 'desc');

        if ($request->filled('state')) {
            $DB->where('state', $request->input('state'));
        }

        if ($request->filled('store_id')) {
            $DB->where('store_id', $request->input('store_id'));
        }
        $DB->orderBy('add_time', 'desc');

        $total = $DB->count();

        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10));
        }

        if ($request->filled('page_size')) {
            $DB->limit($request->input('page_size', 10));
        }

        $result = $DB->get();

        $result->map(function ($item) {

            $item->payInfo = DB::table('pay')->where('pay_id', $item->pay_id)->get();
            $item->storeInfo = DB::table('stores')->where('id', $item->store_id)->first();
            $item->addressInfo = DB::table('order_address')->where('id', $item->address_id)->first();
            $item->userInfo = DB::table('users')->where('id', $item->user_id)->first();

            $item->snapshotInfo = DB::table('snapshot')->where('order_id', $item->order_id)->get();
            $item->snapshotInfo = $item->snapshotInfo->map(function ($el) {
                $el->data = json_decode($el->data, true);
                return $el;
            });
            return $item;
        });

        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData($result->count(), 'OK', $data);
    }

    public function info(Request $request)
    {

        $order_id = $request->input('order_id');
        if (!$order_id) {
            return $this->jsonData(-1, 'ERR order_id');
        }
        $DB = DB::table('order')->orderBy('add_time', 'desc');
        $result = $DB->where('order_id', $order_id)->first();

        $result->snapshotInfo = DB::table('snapshot')->where('order_id', $result->order_id)->get();
        $result->addressInfo = DB::table('order_address')->where('id', $result->address_id)->first();

        $result->snapshotInfo = $result->snapshotInfo->map(function ($el) {
            $el->data = json_decode($el->data, true);
            return $el;
        });

        if (!$result) {
            return $this->jsonData(-1, 'ERR');
        }
        return $this->jsonData(1, 'OK', $result);
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
