<?php

use Illuminate\Support\Facades\Route;

Route::namespace('H5')->prefix('h5')->group(function () {

    // 登陆
    Route::prefix('auth')->group(function () {
        Route::any('openid', 'AuthController@openid');
        Route::any('login', 'AuthController@login');
    });

    //支付回调
    Route::any('pay/wx_notify_url', 'PayController@wx_notify_url');

    Route::middleware(['core', 'auth'])->group(function () {
        //门店
        Route::prefix('store')->group(function () {
            Route::any('list', 'StoreController@list');
            Route::any('save', 'StoreController@save');
            Route::any('info', 'StoreController@info');
        });

        //分类
        Route::prefix('class')->group(function () {
            Route::any('list', 'ClassiController@list');
        });

        //商品
        Route::prefix('goods')->group(function () {
            Route::any('list', 'GoodsController@list');
            Route::any('info', 'GoodsController@info');
        });

        //收货地址
        Route::prefix('address')->group(function () {
            Route::any('list', 'AddressController@list');
            Route::any('save', 'AddressController@save');
            Route::any('info', 'AddressController@info');
            Route::any('del', 'AddressController@del');
        });

        //订单
        Route::prefix('order')->group(function () {
            Route::any('create', 'OrderController@create');
            Route::any('list', 'OrderController@list');
            Route::any('info', 'OrderController@info');
            //申请取消订单
            Route::any('close_order', 'OrderController@closeOrder');
            //确认收货
            Route::any('success', 'OrderController@success');
            //兑换水票
            Route::any('coupon_exchange', 'OrderController@coupon_exchange');
        });

        //支付
        Route::prefix('pay')->group(function () {
            Route::any('getH5', 'PayController@getH5');
        });

        //水票
        Route::prefix('water_coupon')->group(function () {
            Route::any('create', 'WaterCouponController@buyWaterCoupon');
            Route::any('list', 'WaterCouponController@list');
            Route::any('user/list', 'WaterCouponController@user_list');
            Route::any('info', 'WaterCouponController@info');
            Route::any('user/info', 'WaterCouponController@user_info');
            // 历史水票接口
            Route::any('/list/history', 'WaterCouponController@user_list_history');
        });

        //用户信息
        Route::prefix('user')->group(function () {
            Route::any('info', 'UserController@info');
            Route::any('user_store', 'UserController@user_store');
        });


        //意见反馈
        Route::prefix('feedback')->group(function () {
            Route::any('save', 'FeedbackController@save');
        });
    });
});
