<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FileController extends Controller
{
    public function upload(Request $req)
    {

        $file = $req->file('file');
        $fileName = $file->getClientOriginalName();
        $suffix = $file->getClientOriginalExtension();
        $path = $file->move(
            'public/files/' .  date('Ymd', time()),
            date('Ymdhis', time()) . rand(1000, 9999) . '.' . $suffix
        );

        $data = [];
        $data['file_name'] = $fileName;
        $data['url'] =  '/' . $path->getPathname();

        $result =  DB::table('file')->insert($data);

        return response()->json([
            'code' => 1,
            'msg' => 'success',
            'data' => $data,
        ]);
    }
}
