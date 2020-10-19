<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Erp')->prefix('erp')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::any('login', 'AuthController@pwdLogin');
        Route::any('reg', 'AuthController@reg');
    });

    Route::middleware(['core', 'auth'])->group(function () {

        Route::prefix('store')->group(function () {
            Route::any('list', 'StoreController@list');
            Route::any('save', 'StoreController@save');
            Route::any('info', 'StoreController@info');
            Route::any('del', 'StoreController@del');
        });

        Route::prefix('user')->group(function () {
            Route::any('list', 'UserController@list');
            Route::any('save', 'UserController@save');
            Route::any('info', 'UserController@info');
            Route::any('del', 'UserController@del');
        });

        Route::prefix('order')->group(function () {
            Route::any('list', 'OrderController@list');
            Route::any('save', 'OrderController@save');
            Route::any('info', 'OrderController@info');
            Route::any('del', 'OrderController@del');
        });

        //意见反馈
        Route::prefix('feedback')->group(function () {
            Route::any('save', 'FeedbackController@save');
        });

        Route::prefix('distribution')->group(function () {
            Route::any('qrcode', 'DistributionController@qrcode');
        });
    });
});
