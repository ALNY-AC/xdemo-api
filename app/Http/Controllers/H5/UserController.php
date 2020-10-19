<?php

namespace App\Http\Controllers\H5;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ResponseJson;

    public function info(Request $request)
    {
        $id = $request->get('jwt')->id;
        if (!$id) {
            return $this->jsonData(-1, 'ERR');
        }

        $result = DB::table('users')
            ->where('id', $id)
            ->first();

        return $this->jsonData(1, 'OK', $result);
    }

    //
    public function user_store(Request $request)
    {
        $user_id = $request->get('jwt')->id;
        $list = DB::table('user_store')
            ->leftJoin('stores', 'user_store.store_id', '=', 'stores.id')
            ->where('user_store.user_id', $user_id)
            ->orderBy('user_store.add_time', 'DESC')
            ->get();

        $data = [];
        $data['list'] = $list;
        $data['total'] = $list->count();
        return $this->jsonData(1, 'OK', $data);
    }
}
