<?php

namespace App\Http\Controllers\Mini;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Model\Store;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use EasyWeChat\Factory;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    use ResponseJson;

    public function login(Request $request)
    {
        $phone_info = $request->input('phone_info');
        $user_info = $request->input('user_info');
        $code = $request->input('code');
        $share_id = $request->input('share_id', '');
        if ($share_id) {
            $shareInfo = DB::table('mini_qrcode')
                ->where('id', $share_id)
                ->first();
            if ($shareInfo) {
                $share_id = $shareInfo->user_id;
            } else {
                $share_id = '';
            }
        }

        $config = [
            'app_id' => 'wxa8d21fe24928aca8',
            'secret' => 'ac354461dbe7ac76170f1264255541aa',
        ];

        $app = Factory::miniProgram($config);

        $session = $app->auth->session($code);
        Log::info('wx_session:' . json_encode($session, JSON_UNESCAPED_UNICODE));
        if (!$session['session_key'] || !$phone_info['iv']) {
            return $this->jsonData(-1, 'ERR session_key or iv');
        }

        $phoneData = $app->encryptor->decryptData($session['session_key'], $phone_info['iv'], $phone_info['encryptedData']);
        $userData = $app->encryptor->decryptData($session['session_key'], $user_info['iv'], $user_info['encryptedData']);

        $phone = $phoneData['phoneNumber'];
        $openId = $userData['openId'];

        $jwt = null;
        $user = null;
        if (User::where('openid', $openId)->exists()) {
            // 根据openId筛选已存在
            $user = User::where([
                ['openid', '=', $openId],
                ['user_type', '=', 2],
            ])
                ->first();
            $user->phone = $phone;
            $user->name = $userData['nickName'];
            $user->head = $userData['avatarUrl'];
            $user->gender = $userData['gender'];
            $user->wx_info = json_encode($userData);
            $user->link_id = $share_id;
            $user->save();
            $jwt = encrypt(json_encode($user));
        } else {
            // 没有，新建

            $user = new User();
            $user->phone = $phone;
            $user->openId = $openId;
            $user->name = $userData['nickName'];
            $user->head = $userData['avatarUrl'];
            $user->gender = $userData['gender'];
            $user->user_type = 2;
            $user->wx_info = json_encode($userData);
            $user->link_id = $share_id;
            $user->save();
            $jwt = encrypt(json_encode($user));
        }


        $storeCount = Store::where('user_id', $user->id)->count();


        return response()->json([
            "code" => 1,
            "message" => 'success',
            "jwt" => $jwt,
            "phoneData" => $phoneData,
            "userData" => $userData,
            "userInfo" => $user,
            "session" => $session,
            "storeCount" => $storeCount,
        ]);
    }


    public function pwd_login(Request $request)
    {
        // 0:超级管理员
        // 1:普通用户
        // 2:商户
        // 3:分销员或员工

        $user = DB::table('users')
            ->where([
                ['phone', '=', $request->input('account')],
                ['user_type', '=', 2],
            ])
            // ->orWhere([
            //     ['name', '=', $request->input('account')],
            //     ['user_type', '=', 0],
            // ])
            ->first();
        if ($user && $user->pwd == md5($_ENV['APP_KEY'] . $request->input('password'))) {

            $jwt = encrypt(json_encode($user));

            return response()->json([
                'code' => 1,
                'msg' => 'success',
                'userInfo' => $user,
                'jwt' => $jwt,
            ]);
        } else {
            return response()->json([
                'code' => -1,
                'msg' => 'error',
            ]);
        }
        return [$user];
    }
}
