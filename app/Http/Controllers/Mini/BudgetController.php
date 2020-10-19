<?php

namespace App\Http\Controllers\Mini;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{

    use ResponseJson;

    public function list(Request $request)
    {
        $time = $request->input('times', 0);
        $store_id = $request->input('store_id', '');
        $type = $request->input('type', '');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }
        $start_time = $time;
//        $end_time = $time[1];
        $end_time = $time . ' 23:59:59';
        $DB = DB::table('budget')
            ->where([
                ['store_id', '=', $store_id],
            ])
            ->orderBy('add_time', 'desc');
        if ($type) {
            $DB->where('type', $type);
        }
        if ($time) {
            $DB->where([
                ['add_time', '>', $start_time],
                ['add_time', '<', $end_time],
            ]);
        }
        $total = $DB->count();
        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10));
        }
        if ($request->filled('page_size')) {
            $DB->limit($request->input('page_size', 10));
        }
        $result = $DB->get();

        $data['list'] = $result;
        $data['total'] = $total + 0;

        return $this->jsonData($result->count(), 'OK', $data);
    }

    public function info(Request $request)
    {
        $id = $request->input('id');
        $data = DB::table('budget')
            ->where('id', $id)
            ->first();

        return $this->jsonData(1, 'OK', $data);
    }

    public function store_profile(Request $request)
    {
        $store_id = $request->input('store_id', '');
        $store_profile = DB::table('store_profile')
            ->where('store_id', $store_id)
            ->first();

        return $this->jsonData(1, 'OK', $store_profile);

    }

    //提现
    public function getMoney(Request $request)
    {
        $store_id = $request->input('store_id', '');
        $money_type = $request->input('money_type', '');
        $account = $request->input('account', '');
        $real_name = $request->input('real_name', '');
        $money = $request->input('money', '');
        $text = $request->input('text', '');
        $user_id = $request->get('jwt')->id;
        if (!$money_type || !$real_name || !$money || !$user_id) {
            return $this->jsonData(-1, 'ERR');
        }
        if ($money < 10) {
            return $this->jsonData(-1, '最低提现十元～');
        }
        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $money)) {
            return $this->jsonData(-1, '提现金额最小精确到分～');
        }
        //查询用户余额
        $storeInfo = DB::table('stores')
            ->leftJoin('store_profile', 'stores.id', '=', 'store_profile.store_id')
            ->where([
                ['store_id', '=', $store_id],
//                ['user_id', '=', $user_id],
            ])
            ->first();
        if ($money > $storeInfo->money) {
            return $this->jsonData(-1, '余额不足');
        }
        $total_royalty = sprintf("%.2f", $money * $storeInfo->money_royalty);
        $data = [
            'store_id' => $store_id,
            'money' => $money,
            'type' => 2,    //商家：1收入2支出
            'money_type' => $money_type,
            'account' => $account,
            'real_name' => $real_name,
            'state' => 0, //'状态0审核中1通过2驳回',
            'pay_id' => 'NO' . Carbon::now()->format('YmdHis') . rand(10000, 99999),
            're_money' => $money - $total_royalty,
            'total_royalty' => $total_royalty,
            'money_royalty' => $storeInfo->money_royalty,
            'text' => $text,
            'budget_type' => 3,
        ];
        $res = DB::table('budget')
            ->where('store_id', $store_id)
            ->insert($data);
        if (!$res) {
            return $this->jsonData(-1, '操作失败');
        }
        //减

        DB::beginTransaction(); //开启事务
        $decrement = DB::table('store_profile')
            ->where('store_id', $store_id)
            ->decrement('money', $money);
        //加
        $increment = DB::table('store_profile')
            ->where('store_id', $store_id)
            ->increment('freeze_money', $money);

        if ($decrement && $increment) {   //判断两条同时执行成功
            DB::commit();  //提交
            return $this->jsonData(1, 'OK');
        } else {
            DB::rollback();  //回滚
            return $this->jsonData(-1, 'ERR');
        }
    }
}
