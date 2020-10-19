<?php

namespace App\Http\Controllers\H5;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Controllers\User\User;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use EasyWeChat\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use ResponseJson;

    public function http($url, $data = [])
    {
        // $data = ['code' => 1, "token" => 2];
        $data = collect($data);
        $data = $data->map(function ($v, $k) {
            return "$k=$v";
        });
        $data = $data->values()->toArray();
        $data = implode(';', $data);

        $curlobj = curl_init();
        curl_setopt($curlobj, CURLOPT_URL, $url);
        curl_setopt($curlobj, CURLOPT_USERAGENT, "user-agent:Mozilla/5.0 (Windows NT 5.1; rv:24.0) Gecko/20100101 Firefox/24.0");
        curl_setopt($curlobj, CURLOPT_HEADER, 0);          //启用时会将头文件的信息作为数据流输出。这里不启用
        curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, 1);  //如果成功只将结果返回，不自动输出任何内容。如果失败返回FALSE
        curl_setopt($curlobj, CURLOPT_POST, 1);            //如果你想PHP去做一个正规的HTTP POST，设置这个选项为一个非零值。这个POST是普通的 application/x-www-from-urlencoded 类型，多数被HTML表单使用。
        curl_setopt($curlobj, CURLOPT_POSTFIELDS, $data);  //需要POST的数据
        curl_setopt($curlobj, CURLOPT_HTTPHEADER, array("application/x-www-form-urlencoded;  
															charset=utf-8", "Content-length: " . strlen($data)));
        $rtn = curl_exec($curlobj);
        return json_decode($rtn, true);
    }

    public function openid(Request $request)
    {
        $code = $request->input('code');
        $appid = env('WX_WECHAT_APP_ID');
        $secret = env('WX_WECHAT_SECRET');
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=$code&grant_type=authorization_code";
        $data = $this->http($url);

        return $this->jsonData(1, 'OK', $data);
    }

    public function login(Request $request)
    {

        $data = $request->toArray();
        $openid = $data['openid'];
        $access_token = $data['access_token'];
        // 0:超级管理员
        // 1:普通用户
        // 2:商户
        // 3:分销员或员工

        //        $unionid = $data['unionid'];
        $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
        $wx_userInfo = $this->http($url);

        $User = DB::table('users');
        $is = $User
            ->where([
                ['openid', '=', $openid],
                ['user_type', '=', 1],
            ])
            ->first();

        if (!$is) {
            // 不存在，就添加
            $User->insert([
                "openid" => $openid,
                "user_type" => 1,
                //                "unionId" => $unionid,
                "wx_info" => json_encode($wx_userInfo),
                "name" => $wx_userInfo['nickname'],
                "head" => $wx_userInfo['headimgurl'],
                //                "from_id" => $request->filled('from_id') ? $request->input('from_id') : '',
            ]);
        }

        $userInfo = $User->where('openid', $openid)->first();

        $jwt = encrypt(json_encode($userInfo));

        return [
            "code" => 1,
            "msg" => "success",
            "data" => $userInfo,
            'jwt' => $jwt,
        ];
    }
}


// avatarUrl: "https://wx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTLt51Sq1c4aicK3OVMpOazFlDzfTe5yJUP1PDpKyCDyJeBiauzAlIsBMKUqfSRuud2XKWJUPJTickVtw/132"
// city: "Xuhui"
// country: "China"
// gender: 1
// language: "zh_CN"
// nickName: "敲代码的"
// openId: "oj92Q4vVfs8FJVrnUxIDT1JW-Tas"
// province: "Shanghai"
// unionId: "oMJGssz88vjRihjnyBVI9CMCifkg"
// watermark:
// 			appid: "wx9f4a9bdc95bcc3d7"
// 			timestamp: 1573177103
