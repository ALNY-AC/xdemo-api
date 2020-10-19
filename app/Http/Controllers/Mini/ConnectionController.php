<?php

namespace App\Http\Controllers\Mini;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConnectionController extends Controller
{
    use ResponseJson;

    //人员管理
    public function list(Request $request)
    {
        $store_id = $request->input('store_id', '');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }
        $DB = DB::table('admin_store')
            ->select('users.*')
            ->leftJoin('users', 'admin_store.user_id', '=', 'users.id')
            ->where([
                ['store_id', '=', $store_id],
                ['admin_store.data_state', '=', 1],
            ])
            ->orderBy('admin_store.add_time', 'desc');
        $total = $DB->count() + 0;
        if ($request->filled('page')) {
            $DB->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10))
                ->limit($request->input('page_size', 10));
        }
        $result = $DB->get();
        $data['list'] = $result;
        $data['total'] = $total;
        return $this->jsonData($result->count(), 'OK', $data);
    }

    //添加
    public function add_admin(Request $request)
    {
        $user_id = $request->get('jwt')->id;
        $store_id = $request->input('store_id', '');
        if (!$store_id) {
            return $this->jsonData(-1, 'ERR');
        }
        $storeInfo = DB::table('stores')
            ->where('id', $store_id)
            ->first();
        if (!$storeInfo) {
            return $this->jsonData(-1, '门店不存在');
        }
        if ($storeInfo->user_id === $user_id) {
            return $this->jsonData(-1, '请勿添加自己');
        }
        $storeAdmin = DB::table('admin_store')
            ->where([
                ['store_id', '=', $store_id],
                ['user_id', '=', $user_id],
            ])
            ->first();
        if ($storeAdmin) {
            if ($storeAdmin->data_state === 1) {
                return $this->jsonData(-1, '请勿重复添加');
            } else {
                $res = DB::table('admin_store')
                    ->where([
                        ['store_id', '=', $store_id],
                        ['user_id', '=', $user_id],
                    ])
                    ->update([
                        'data_state' => 1
                    ]);
            }
        } else {
            $res = DB::table('admin_store')
                ->insert([
                    'store_id' => $store_id,
                    'user_id' => $user_id,
                ]);
        }
        if (!$res) {
            return $this->jsonData(-1, '关联失败');
        }
        return $this->jsonData(1, 'OK');
    }

    //解除关联
    public function del_connection(Request $request)
    {
        $user_id = $request->input('user_id', '');
        $store_id = $request->input('store_id', '');
        if (!$user_id) {
            $user_id = $request->get('jwt')->id;
        }
        $storeInfo = DB::table('stores')
            ->where('id', $store_id)
            ->first();
        if (!$storeInfo) {
            return $this->jsonData(-1, '门店不存在');
        }
        if ($storeInfo->user_id === $user_id) {
            return $this->jsonData(-1, '不能删除自己的门店');
        }
        $storeAdmin = DB::table('admin_store')
            ->where([
                ['store_id', '=', $store_id],
                ['user_id', '=', $user_id],
            ])
            ->first();
        if (!$storeAdmin) {
            return $this->jsonData(-1, '账号没有与店铺关联');
        }
        if ($storeAdmin->data_state === 0) {
            return $this->jsonData(1, 'OK');
        }
        $res = DB::table('admin_store')
            ->where([
                ['store_id', '=', $store_id],
                ['user_id', '=', $user_id],
            ])
            ->update([
                'data_state' => 0
            ]);
        if (!$res) {
            return $this->jsonData(-1, '操作失败');
        }
        return $this->jsonData(1, 'OK');
    }
}
