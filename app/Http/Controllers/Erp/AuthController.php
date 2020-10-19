<?php

namespace App\Http\Controllers\Erp;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{  // @todo AuthController 这里是要生成的类名字

    public function __construct($value = '')
    { // 这里就是要生成的类文件的通用代码了
        # code...
    }

    public function create(Request $request)
    {
        // $name = $request->input('phone');
        // 		$results = DB::select("SELECT * FROM user");
        dump('success');
        die;
        $data = [
            'phone' => 'root',
            'pwd' => md5($_ENV['APP_KEY'] . '123'),
            'power_group_id' => 1,
        ];

        DB::table('user')->insert($data);


        return response()->json([
            'code' => 1,
            'msg' => '',
            'data' => $data
        ]);
    }
    public function reg(Request $request)
    {
        // 数据状态
        // -1:被删除
        // 0:封禁、小黑屋
        // 1:正常
        // 2:待审核
        if (!$request->input('account')) {
            return response()->json([
                'code' => -1,
                'msg' => '账户不能为空',
                'data' => ''
            ]);
        }

        $data = $request->toArray();


        // 判断密码是否一致
        if ($data['password'] != $data['password2']) {
            return response()->json([
                'code' => -1,
                'msg' => '两次输入的密码不一致！',
                'data' => $data,
            ]);
        }
        // 是否重复注册


        if (DB::table('users')
            ->where('phone', $data['account'])
            ->where('user_type', 3)
            ->exists()
        ) {
            return response()->json([
                'code' => -2,
                'msg' => '用户已存在！请勿重复注册！',
                'data' => $data,
            ]);
        }
        // "invite_code" =>  $data['invite_code'],

        $newUser = [
            "phone" => $data['account'],
            "pwd" => md5($_ENV['APP_KEY'] . $data['password']),
            "real_name" =>  $data['real_name'],
            "user_type" =>  3,
            "data_state" =>  2,
        ];

        $result = DB::table('users')->insert($newUser);

        if ($result) {
            return response()->json([
                'code' => 1,
                'msg' => '注册成功',
                'data' => $result
            ]);
        } else {
            return response()->json([
                'code' => -3,
                'msg' => '注册失败',
                'data' => $result
            ]);
        }
    }
    public function pwdLogin(Request $request)
    {
        if (!$request->input('account')) {
            return response()->json([
                'code' => -1,
                'msg' => '账户不能为空',
                'data' => ''
            ]);
        }
        $user = DB::table('users')
            ->where([
                ['phone', '=', $request->input('account')],
                ['user_type', '=', 3],
            ])
            ->first('*');

        //		 $md = md5($_ENV['APP_KEY'] . '123123');
        //		 return [$user->pwd, $md];

        if ($user && $user->pwd == md5($_ENV['APP_KEY'] . $request->input('password'))) {

            unset($user->pwd, $user->edit_time, $user->add_time, $user->wx_info);

            $jwt = encrypt(json_encode($user));

            return response()->json([
                'code' => 1,
                'msg' => '登录成功',
                'data' => $user,
                'jwt' => $jwt,
            ]);
        } else {
            return response()->json([
                'code' => -1,
                'msg' => '登录失败',
                'data' => null
            ]);
        }

        // $code = $request->input('code');

        // $appid = 'wx754474ce7640bd0c';
        // $secret = '810c6117c5d0d61392744fde8e8cd010';
        // $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=$code&grant_type=authorization_code';

        // $data = $this->http($url);

        // $access_token = $data['access_token'];
        // $openid = $data['openid'];
        // $unionid=  $data['unionid'];
        // $url = "https://api.weixin.qq.com/sns/userinfo?access_token=$access_token&openid=$openid&lang=zh_CN";
        // $wx_userInfo = $this->http($url);

        // $User = DB::table('user');

        // $is = $User->where('unionid' , $unionid)->exists();

        // if(!$is) {
        // 	$userData = [

        // 		"openid" => $openid,
        // 		"unionid" => $unionid,
        // 		"wx_head" => $wx_userInfo['headimgurl'],
        // 		"wx_name" => $wx_userInfo['nickname'],

        // 	];

        // 	$user = DB::table('user');
        // 	$User->insert($userData);
        // }

        // $userInfo = $User->where('unionid' , $unionid)->first();

        // $jwt = encrypt(json_encode($userInfo));

        // return [
        // 	"code" => 1,
        // 	"msg" => "success",
        // 	"data" => $userInfo,
        // 	'jwt' => $jwt,
        // ];

    }

    // public function http($url , $data = [])
    // {

    // 	$data = collect($data);
    // 	$data = $data->map( function ($v , $k)  {

    // 		return "$k = $v";

    // 	});

    // 	$data = $data->values()->toArray();
    // 	$data = implode(';' , $data);

    // 	$curlobj = curl_init();

    // 	curl_setopt($curlobj , CURLOPT_URL , $url);
    // 	curl_setopt($curlobj , CURLOPT_USERAGENT , "user-agent:Mozilla/5.0 (Windows NT 5.1; rv:24.0) Gecko/20100101 Firefox/24.0");
    // 	curl_setopt($curlobj , CURLOPT_HEADER , 0);
    // 	curl_setopt($curlobj , CURLOPT_RETURNTRANSFER , 1);
    // 	curl_setopt($curlobj , CURLOPT_PORT , 1);
    // 	curl_setopt($curlobj , CURLOPT_POSTFIELDS , $data);
    // 	curl_setopt($curlobj , CURLOPT_HTTPHEADER , array(
    // 										"application/x-www-form-urlencoded;
    // 										charset=utf-8", "Content-length: " . strlen($data)));

    // 	$rtn = curl_exec($curlobj);

    // 	return json_decode($rtn , true);

    // }

}
