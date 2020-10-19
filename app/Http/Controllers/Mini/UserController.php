<?php

namespace App\Http\Controllers\Mini;

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
}
