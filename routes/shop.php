<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::namespace('Shop')->middleware(['cors'])->prefix('shop')->group(function () {

    Route::prefix('class')->group(function () {
        Route::any('list', 'ClassController@list');
    });

    Route::prefix('prop')->group(function () {
        Route::any('list', 'PropController@list');
        Route::any('save', 'PropController@save');
        Route::any('del', 'PropController@del');
        Route::any('info', 'PropController@info');
    });

    Route::prefix('prop/group')->group(function () {
        Route::any('list', 'PropController@list');
        Route::any('save', 'PropController@save');
        Route::any('del', 'PropController@del');
        Route::any('info', 'PropController@info');
    });
});
