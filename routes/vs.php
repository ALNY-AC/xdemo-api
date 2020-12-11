<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::namespace('VS')->middleware(['cors'])->prefix('vs')->group(function () {

    Route::any('list', 'VsController@list');
    Route::any('save', 'VsController@save');
    Route::any('info', 'VsController@info');
    Route::any('del', 'VsController@del');


    Route::any('/enum', function (Request $request) {
        $enmu = [];
        for ($i = 0; $i < 3; $i++) {
            $item = [
                'value' => "$i",
                'label' => "选项$i",
            ];
            $enmu[] = $item;
        }

        return [
            "data" => $enmu
        ];
    });

    Route::any('/data', function (Request $request) {
        // for ($i = 0; $i < 10; $i++) {
        //     $item = [
        //         'name' => "数据",
        //     ];
        //     DB::table('time')->insert($item);
        // }

        $DB = DB::table('time');

        if ($request->filled('name')) {
            $DB->where('name', 'like', '%' . $request->input('name') . '%');
        }

        $result = $DB->get();
        return [
            "data" => $result
        ];
    });
});
