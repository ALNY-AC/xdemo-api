<?php

namespace App\Http\Controllers\Vs;

use App\Http\Controllers\Controller;
use App\Http\Response\ResponseJson;
use App\Model\Vsproject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VsController extends Controller
{
    use ResponseJson;

    public function save(Request $request)
    {

        // vsproject
        $Vsproject = DB::table('vsproject');


        if ($request->filled('id')) {
            $result = $Vsproject->where('id', $request->input('id'))
                ->update($request->toArray());
            return response()->json([
                'code' => 1,
                'msg' =>  'success',
                'data' =>  $result,
            ]);
        } else {

            $result = $Vsproject->insertGetId($request->toArray());
            return response()->json([
                'code' => 1,
                'msg' =>  'success',
                'data' =>  $result,
            ]);
        }
    }

    public function info(Request $request)
    {
        $info =  DB::table('vsproject')->where('id', $request->input('id'))->first();
        return response()->json([
            'code' => 1,
            'msg' =>  'success',
            'data' =>  $info,
        ]);
    }

    public function list(Request $request)
    {
        $Vsproject = DB::table('vsproject');
        if ($request->filled('name')) {
            $Vsproject = $Vsproject->where('name', 'like', '%' . $request->input('name') . '%');
        }


        $total = $Vsproject->count();
        if ($request->filled('page')) {
            $Vsproject = $Vsproject->offset(($request->input('page', 1) - 1) * $request->input('page_size', 10));
        }
        if ($request->filled('page_size')) {
            $Vsproject = $Vsproject->limit($request->input('page_size', 10));
        }

        $data = $Vsproject->get();

        return response()->json([
            'code' => 1,
            'msg' =>  'success',
            'data' =>  $data,
            'count' =>  $total,
        ]);
    }

    // public function del(Request $request)
    // {
    //     $Vsproject = Vsproject::where('id', $request->input('id'))->delete();

    //     return $this->jsonData(1, 'Ok');
    // }
}
