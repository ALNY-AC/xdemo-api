<?php

namespace App\Http\Controllers\Ctos;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Model\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    use ResponseJson;

    public function save(Request $request)
    {

        if ($request->filled('id')) {

            $result = Store::where('id', $request->input('id'))
                ->update($request->all());

            if ($result >= 0) {
                return $this->jsonData(1, '保存成功', $result);
            } else {
                return $this->jsonData(-1, '保存失败', $result);
            }
        } else {
            $store = new Store($request->all());
            $store->user_id = $request->get('jwt')->id;
            $result = $store->save();
            if ($result) {
                return $this->jsonData(1, '保存成功', $result);
            } else {
                return $this->jsonData(-1, '保存失败', $result);
            }
        }
    }

    public function profile_save(Request $request)
    {
        if (!$request->filled('id')) {
            return $this->jsonData(-1, 'ERR');
        }
        $result = DB::table('store_profile')
            ->where('store_id', $request->input('id'))
            ->update($request->all());

        if ($result >= 0) {
            return $this->jsonData(1, '保存成功', $result);
        } else {
            return $this->jsonData(-1, '保存失败', $result);
        }

    }

    public function profile_info(Request $request)
    {
        if (!$request->filled('id')) {
            return $this->jsonData(-1, 'ERR');
        }
        $result = DB::table('store_profile')
            ->where('store_id', $request->input('id'))
            ->first();
        return $this->jsonData(1, 'OK', $result);
    }

    public function info(Request $request)
    {
        $store = Store::find($request->input('id'));

        return $this->jsonData(1, 'Ok', $store);
    }

    public function list(Request $request)
    {
        $store = DB::table('stores');

        $store->orderBy('add_time', 'desc');

        if ($request->filled('name')) {
            $store = $store->where('name', 'like', '%' . $request->input('name') . '%');
        }
        if ($request->filled('is_up')) {
            $store = $store->where('is_up', $request->input('is_up'));
        }

        $total = $store->count();
        if ($request->filled('page')) {
            $store = $store->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10));
        }
        if ($request->filled('page_size')) {
            $store = $store->limit($request->input('page_size', 10));
        }
        $data = $store->get();

        return $this->jsonData($data->count(), 'success', ['list' => $data, "total" => $total]);
    }

    public function del(Request $request)
    {
        $store = Store::where('id', $request->input('id'))->delete();

        return $this->jsonData(1, 'Ok');
    }
}
