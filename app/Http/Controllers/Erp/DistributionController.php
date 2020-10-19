<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\QrcodeController;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistributionController extends Controller
{
    use ResponseJson;

    const LOGIN_PATH = 'pages/login/login';

    public function qrcode(Request $request)
    {
        $user_id = $request->get('jwt')->id;

        $qrInfo = DB::table('mini_qrcode')
            ->where([
                ['user_id', '=', $user_id],
                ['type', '=', 1],
            ])
            ->first();

        if (!$qrInfo) {
            return (new QrcodeController())->qrcode($user_id, 1, self::LOGIN_PATH);
        }

        $userInfo = DB::table('users')
            ->where('id', $user_id)
            ->first();
        if (!$userInfo->mini_qrcode) {
            DB::table('users')
                ->where('id', $user_id)
                ->update([
                    'mini_qrcode' => $qrInfo->qrcode
                ]);
        }
        return $this->jsonData(1, '申请成功');
    }

}
