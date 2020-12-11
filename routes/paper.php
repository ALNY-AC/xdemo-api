<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::namespace('Paper')->middleware(['cors'])->prefix('paper')->group(function () {

    Route::any('list', 'PaperController@list');
    Route::any('save', 'PaperController@save');
    Route::any('info', 'PaperController@info');
    Route::any('del', 'PaperController@del');
  
});
