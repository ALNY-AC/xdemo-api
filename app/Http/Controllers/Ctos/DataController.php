<?php

namespace App\Http\Controllers\Ctos;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Lib\Message\CCP\SDK\CCPRestSDK;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Listeners\Random;
use Illuminate\Support\Carbon;

// include_once("../SDK/CCPRestSDK.php");


class DataController extends Controller
{  // @todo AuthController 这里是要生成的类名字


    private $day = 30;

    public function info1(Request $request)
    {
        $test = [];
        $this->day = $request->input('day', $this->day);
        $user = $this->getUserData();
        $store = $this->getStoreData();

        return [
            'code' => 1,
            'msg' => 'success',
            'data' => [
                "user" => $user,
                "store" => $store,
            ]
        ];
    }

    public function getDay()
    {
        $days = [];
        for ($i = 1; $i <= 30; $i++) {
            $days[] =  Carbon::parse("-$i days")->format('Y-m-d');
        }
        $days = array_reverse($days);
        return $days;
    }

    public function query($days, $table)
    {
        foreach ($days as $v) {
            $DB = DB::table($table);
            $data[] = $DB
                ->where('add_time', '>', "$v 00:00:00")
                ->where('add_time', '<', "$v 23:59:59")
                ->count();
        }
        return $data;
    }

    public function getUserData()
    {
        $days = $this->getDay();
        $data = $this->query($days, 'user');

        foreach ($days as $k => $v) {
            $days[$k] =  Carbon::parse($v)->format('d');
        }
        $user = [
            "x" => $days,
            "data" => $data
        ];
        return $user;
    }

    public function getStoreData()
    {
        $days = $this->getDay();
        $data = $this->query($days, 'store');

        foreach ($days as $k => $v) {
            $days[$k] =  Carbon::parse($v)->format('d');
        }
        $user = [
            "x" => $days,
            "data" => $data
        ];
        return $user;
    }

    public function info(Request $request)
    {

        // columns: ['日期', '访问用户', '下单用户', '下单率'],
        // rows: [
        //     { '日期': '1/1', '访问用户': 1393, '下单用户': 1093, '下单率': 0.32 },
        //     { '日期': '1/2', '访问用户': 3530, '下单用户': 3230, '下单率': 0.26 },
        //     { '日期': '1/3', '访问用户': 2923, '下单用户': 2623, '下单率': 0.76 },
        //     { '日期': '1/4', '访问用户': 1723, '下单用户': 1423, '下单率': 0.49 },
        //     { '日期': '1/5', '访问用户': 3792, '下单用户': 3492, '下单率': 0.323 },
        //     { '日期': '1/6', '访问用户': 4593, '下单用户': 4293, '下单率': 0.78 }
        // ]



        $columns = ['日期', '注册用户', '注册商户', '下单量'];
        $rows = [];
        for ($i = 0; $i <= $this->day; $i++) {
            $rows[]['日期'] = \Carbon\Carbon::parse("-$i day")->toDateString();
        }

        // 0:超级管理员
        // 1:普通用户
        // 2:商户
        // 3:分销员或员工
        foreach ($rows as $k => $v) {

            $users1 = DB::table('users')
                ->where('user_type', 1)
                ->whereDate('add_time', $v['日期'])
                ->count();

            $users2 = DB::table('users')
                ->where('user_type', 2)
                ->whereDate('add_time', $v['日期'])
                ->count();

            $order = DB::table('order')
                ->where('state', '>', 0)
                ->where('state', '<>', 5)
                ->where('state', '<>', 9)
                ->where('state', '<>', 21)
                ->whereDate('add_time', $v['日期'])
                ->count();

            $v['注册用户'] = $users1;
            $v['注册商户'] = $users2;
            $v['下单量'] = $order;
            // 0待支付
            // 1已支付等待送货
            // 2商家确认配送中
            // 4完成
            // 5订单取消
            // 9支付超时取消
            // 21客户取消订单
            $rows[$k] = $v;
        }


        return [
            'columns' => $columns,
            'rows' => $rows,
        ];


        for ($i = 0; $i <= $this->day; $i++) {
            $x[$i] = DB::table('order')
                ->where('state', 4)
                ->whereDate('add_time', \Carbon\Carbon::parse("-$i day")->toDateString())
                ->count();
            $x1[$i] = DB::table('users')
                ->where('user_type', 2)
                ->whereDate('add_time', \Carbon\Carbon::parse("-$i day")->toDateString())
                ->count();
            $x2[$i] = DB::table('stores')
                ->whereDate('add_time', \Carbon\Carbon::parse("-$i day")->toDateString())
                ->count();
            $y[$i] = substr(\Carbon\Carbon::parse("-$i day")->toDateString(), -2);
        }

        $orders = DB::table('order')
            ->where('state', 4)
            ->count();
        $users = DB::table('users')
            ->where('user_type', 2)
            ->count();
        $stores = DB::table('stores')
            ->count();
        $y = array_reverse($y);
        $data = ['data' => [
            'order' =>
            [
                'data' => array_reverse($x),
                'x' => $y,
                'total' => $orders,
            ],
            'user' =>
            [
                'data' => array_reverse($x1),
                'x' => $y,
                'total' => $users,
            ],
            'store' =>
            [
                'data' => array_reverse($x2),
                'x' => $y,
                'total' => $stores,
            ]
        ]];
        return $data;
    }

    public function infoStore()
    {

        $toDay = \Carbon\Carbon::now()->toDateString() . ' 00:00:00';
        $toWeek = Carbon::now()->subWeek(0)->startOfWeek()->toDateTimeString();
        $toMonth = Carbon::now()->subMonth(0)->startOfMonth()->toDateTimeString();

        // 门店

        $storeTotal = DB::table('stores')
            ->where('data_state', '>', 0)
            ->count();

        $storeToDay = DB::table('stores')
            ->where('add_time', '>=', $toDay)
            ->count();

        $storeToWeek = DB::table('stores')
            ->where('add_time', '>=', $toWeek)
            ->count();

        $storeToMonth = DB::table('stores')
            ->where('add_time', '>=', $toMonth)
            ->count();


        $toDay = \Carbon\Carbon::now()->toDateString() . ' 00:00:00';
        $toWeek = Carbon::now()->subWeek(0)->startOfWeek()->toDateTimeString();
        $toMonth = Carbon::now()->subMonth(0)->startOfMonth()->toDateTimeString();

        // 门店

        $userTotal = DB::table('users')
            ->where('user_type', 2)
            ->count();

        $userToDay = DB::table('users')
            ->where('user_type', 2)
            ->where('add_time', '>=', $toDay)
            ->count();

        $userToWeek = DB::table('users')
            ->where('user_type', 2)
            ->where('add_time', '>=', $toWeek)
            ->count();

        $userToMonth = DB::table('users')
            ->where('user_type', 2)
            ->where('add_time', '>=', $toMonth)
            ->count();


        return [
            "toDay" => $toDay,
            "toWeek" => $toWeek,
            "toMonth" => $toMonth,

            "storeTotal" => $storeTotal,
            "storeToDay" => $storeToDay,
            "storeToWeek" => $storeToWeek,
            "storeToMonth" => $storeToMonth,

            "userTotal" => $userTotal,
            "userToDay" => $userToDay,
            "userToWeek" => $userToWeek,
            "userToMonth" => $userToMonth,
        ];
    }

    public function total()
    {
        // 0:超级管理员
        // 1:普通用户
        // 2:商户
        // 3:分销员或员工

        $userTotal =  DB::table('users')->where('user_type', 1)->count();
        $posTotal =  DB::table('users')->where('user_type', 2)->count();
        $storeTotal = DB::table('stores')->count();


        return [
            "res" => 1,
            "data" => [
                "userTotal" => $userTotal,
                "posTotal" => $posTotal,
                "storeTotal" => $storeTotal,
            ]
        ];
    }
}
