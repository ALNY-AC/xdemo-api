<?php

namespace  App\Http\Controllers; // @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Lib\Dada\Dada;
use App\Lib\Delivery\Delivery;
use App\Lib\Feieyun\HttpClient;
use App\Lib\Http\Http;
use App\Lib\Printer\Printer;
use App\Listeners\Random;
use App\Model\User;
use Illuminate\Support\Facades\DB;
use EasyWeChat\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;


$user = 'asdfasdf';

class JwtController extends Controller
{  // @todo AuthController 这里是要生成的类名字
    public function get(Request $request)
    {

        $User = DB::table('users');


        if ($request->filled('phone')) {
            $phone = $request->input('phone');
            $User->where('phone', $phone);
        }
        if ($request->filled('id')) {
            $id = $request->input('id');
            $User->where('id', $id);
        }

        $user = $User->first();
        if (!$user) {
            dump($user);
            return '用户不存在！';
        }


        $jwt = encrypt(json_encode($user));


        $appid = "wxb21c49c1f4205110";


        $header = 'jwt=' . $jwt . ";app_id=" . $appid;
        // <textarea rows='30' cols='100'>$header</textarea>
        // <h4>header</h4>

        echo "
        <h4>jwt</h4>
        <textarea rows='30' cols='100'>$jwt</textarea>
";
    }
}
