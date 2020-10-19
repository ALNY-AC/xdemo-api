<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::namespace('Mini')->prefix('mini')->group(function () {
    // 在 「App\Http\Controllers\Admin」 命名空间下的控制器
    Route::prefix('auth')->group(function () {
        Route::any('login', 'AuthController@login');
        Route::any('pwd/login', 'AuthController@pwd_login');
    });

    //退款申请回调
    Route::any('pay/wx_refund_notify_url', 'OrderController@wx_refund_notify_url');
    Route::any('activity/wx_notify_url', 'ActivityController@wx_notify_url');

    // 
    Route::middleware('core')->group(function () {
        //门店
        Route::prefix('store')->group(function () {
            Route::any('save', 'StoreController@save');
            Route::any('info', 'StoreController@info');
            Route::any('profile/info', 'StoreController@moneyInfo');
            Route::any('list', 'StoreController@list');
            Route::any('qr_code', 'StoreController@qrCode');

            Route::any('data', 'StoreController@data');
            Route::any('user', 'StoreController@user');
        });

        //分类
        Route::prefix('class')->group(function () {
            Route::any('list', 'ClassiController@list');
            Route::any('info', 'ClassiController@info');
            Route::any('save', 'ClassiController@save');
            Route::any('del', 'ClassiController@del');
        });
        // 商品
        Route::prefix('goods')->group(function () {
            Route::any('list', 'GoodsController@list');
            Route::any('info', 'GoodsController@info');
            Route::any('save', 'GoodsController@save');
            Route::any('del', 'GoodsController@del');
        });

        //水票
        Route::prefix('water_coupon')->group(function () {
            Route::any('save', 'WaterCouponController@save');
            Route::any('list', 'WaterCouponController@list');
            Route::any('info', 'WaterCouponController@info');
            Route::any('del', 'WaterCouponController@del');
            Route::any('user_water_coupon', 'WaterCouponController@user_water_coupon');
        });

        Route::prefix('order')->group(function () {
            Route::any('list', 'OrderController@list');
            Route::any('info', 'OrderController@info');
            Route::any('close_order', 'OrderController@closeOrder');
            Route::any('sending', 'OrderController@sending');
            Route::any('success', 'OrderController@success');
            Route::any('list_count', 'OrderController@list_count');
            Route::any('reject', 'OrderController@reject');
        });

        //收支记录
        Route::prefix('budget')->group(function () {
            Route::any('list', 'BudgetController@list');
            Route::any('info', 'BudgetController@info');
            //申请提现
            Route::any('get_money', 'BudgetController@getMoney');
            Route::any('store_profile', 'BudgetController@store_profile');
        });

        //
        Route::prefix('activity')->group(function () {
            Route::any('order', 'ActivityController@order');
        });

        //
        Route::prefix('sendmsg')->group(function () {
            Route::any('send', 'SendMsgController@send');
        });

        //意见反馈
        Route::prefix('feedback')->group(function () {
            Route::any('save', 'FeedbackController@save');
        });

        Route::prefix('connection')->group(function () {
            Route::any('list', 'ConnectionController@list');
            Route::any('add_admin', 'ConnectionController@add_admin');
            Route::any('del_connection', 'ConnectionController@del_connection');
        });

        Route::prefix('user')->group(function () {
            Route::any('info', 'UserController@info');
        });
    });
});
