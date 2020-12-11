<?php

namespace App\Http\Controllers\Shop;
// @todo: 这里是要生成类的命名空间

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use Illuminate\Http\Request;
use App\Listeners\Random;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PropGroupController extends Controller
{
    public function list(Request $request)
    {
        $DB = DB::table('prop_group');
        $result = $DB->get();
        return response()->json([
            "code" => count($result),
            "msg" => $result ? 'success' : 'error',
            "data" => $result,
        ]);
    }

    public function del(Request $request)
    {
        $DB = DB::table('prop_group');
        $result = -1;
        if ($request->filled('id')) {
            $result =  $DB
                ->where('id', $request->filled('id'))
                ->delete();
            DB::table('prop')
                ->where('group_id', $request->filled('id'))
                ->delete();
        }
        return response()->json([
            "code" => $result >= 0 ? 1 : -1,
            "msg" => $result ? 'success' : 'error',
            "data" => $result,
        ]);
    }

    public function info(Request $request)
    {
        $DB = DB::table('prop_group');
        $result = null;
        if ($request->filled('id')) {
            $DB->where('id', $request->filled('id'));
            $result = $DB->first();
        }
        return response()->json([
            "code" => $result  ? 1 : -1,
            "msg" => $result ? 'success' : 'error',
            "data" => $result,
        ]);
    }


    public function save(Request $request)
    {

        $result = -1;
        if ($request->filled('id')) {
            $result =  DB::table('prop_group')
                ->where('id', $request->input('id'))
                ->update($request->all());
        } else {
            $result =  DB::table('prop_group')->insert($request->all());
        }

        return response()->json([
            "code" => $result >= 0 ? 1 : -1,
            "msg" => $result >= 0 ? 'success' : 'error',
            "data" => $result,
        ]);
    }
}
