<?php

namespace App\Http\Controllers\Ctos;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{

    use ResponseJson;

    //审核
    public function checkList(Request $request)
    {
        //        $time = $request->input('times', 1);
        //        $start_time = $time[0];
        //        $end_time = $time[1];
        //        $end_time = $end_time.' 23:59:59';
        $state = $request->input('state', '');
        $DB = DB::table('budget')
            ->select('budget.*', 'stores.name')
            ->leftJoin('stores', 'budget.store_id', '=', 'stores.id')
            ->where([
                //                ['add_time', '>', $start_time],
                //                ['add_time', '<', $end_time],
                ['type', '=', 2],
            ])
            ->orderBy('budget.add_time', 'desc');

        if ($state) {
            $DB->where('budget.state', $state);
        } elseif ($state === 0) {
            $DB->where('budget.state', $state);
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

    //通过
    public function success(Request $request)
    {
        $id = $request->input('id', '');
        if (!$id) {
            return $this->jsonData(-1, 'ERR');
        }
        $budgetInfo = DB::table('budget')
            ->where([
                ['id', '=', $id],
                ['state', '=', 0],
            ])
            ->first();
        if (!$budgetInfo) {
            return $this->jsonData(-1, 'ERR');
        }
        DB::beginTransaction(); //开启事务
        $res = DB::table('budget')
            ->where([
                ['id', '=', $id],
            ])
            ->update([
                'state' => 1
            ]);
        //扣除冻结
        $decrement = DB::table('store_profile')
            ->where('store_id', $budgetInfo->store_id)
            ->decrement('freeze_money', $budgetInfo->money);
        if ($decrement && $res) {   //判断两条同时执行成功
            DB::commit();  //提交
            return $this->jsonData(1, 'OK');
        } else {
            DB::rollback();  //回滚
            return $this->jsonData(-1, 'ERR');
        }
    }

    public function reject(Request $request)
    {
        $id = $request->input('id', '');
        $text = $request->input('text', '');
        if (!$id) {
            return $this->jsonData(-1, 'ERR');
        }
        $budgetInfo = DB::table('budget')
            ->where([
                ['id', '=', $id],
                ['state', '=', 0],
            ])
            ->first();

        DB::beginTransaction(); //开启事务
        $res = DB::table('budget')
            ->where('id', $id)
            ->update([
                'state' => 2,
                'text' => $text,
            ]);
        $decrement = DB::table('store_profile')
            ->where('store_id', $budgetInfo->store_id)
            ->decrement('freeze_money', $budgetInfo->money);
        $increment = DB::table('store_profile')
            ->where('store_id', $budgetInfo->store_id)
            ->increment('money', $budgetInfo->money);
        if ($decrement && $increment && $res) {   //判断两条同时执行成功
            DB::commit();  //提交
            return $this->jsonData(1, 'OK');
        } else {
            DB::rollback();  //回滚
            return $this->jsonData(-1, 'ERR');
        }
    }

}
