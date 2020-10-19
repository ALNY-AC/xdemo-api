<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::namespace('Admin')->prefix('admin')->group(function () {
    // 在 「App\Http\Controllers\Admin」 命名空间下的控制器
    Route::prefix('auth')->group(function () {
        Route::any('login', 'AuthController@login');
        Route::any('info', 'AuthController@info');
    });
});
