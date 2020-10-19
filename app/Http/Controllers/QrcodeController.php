<?php

namespace App\Http\Controllers;

use App\Http\Response\ResponseJson;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QrcodeController extends Controller
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

    /**
     * 用户id   类型   小程序地址   数据
     * @param $user_id
     * @param int $type
     * @param string $path
     * @param array $data
     * @return false|string
     */
    public function qrcode($user_id, $type = 0, $path = '', $data = [])
    {
        DB::beginTransaction();
        $qr_id = DB::table('mini_qrcode')
            ->insertGetId([
                'user_id' => $user_id,
                'type' => $type,
            ]);
        $response = $this->app->app_code->getUnlimit($qr_id, [
            'page' => $path,
            'width' => 600,
        ]);
        if (array_key_exists('errcode', $response)) {
            DB::rollback();
            return $this->jsonData(-1, '生成微信图片失败--' . $response['errcode']);
        }
        DB::commit();
        $img_path = '/public/files/' . date('Ymd', time()) . '/' . date('Ymdhis', time()) . rand(1000, 9999) . '.jpg';
        Storage::put($img_path, $response);

        $res = DB::table('mini_qrcode')
            ->where('id', $qr_id)
            ->update([
                'qrcode' => $img_path,
                'data' => json_encode($data),
            ]);
        DB::table('users')
            ->where([
                ['id', '=', $user_id],
                ['user_type', '=', 3],
            ])
            ->update([
                'mini_qrcode' => $img_path
            ]);

        if (!$res) {
            return $this->jsonData(-1, 'ERR');
        }
        return $this->jsonData(1, '申请成功');

    }
}
