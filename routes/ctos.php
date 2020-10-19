<?php

use Illuminate\Support\Facades\Route;

Route::namespace('Ctos')->prefix('ctos')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::any('login', 'AuthController@pwdLogin');
        Route::any('create', 'AuthController@create');
    });

    Route::prefix('store')->group(function () {
        Route::any('list', 'StoreController@list');
        Route::any('save', 'StoreController@save');
        Route::any('profile/save', 'StoreController@profile_save');
        Route::any('info', 'StoreController@info');
        Route::any('del', 'StoreController@del');
        Route::any('profile_info', 'StoreController@profile_info');
    });

    //分类
    Route::prefix('class')->group(function () {
        Route::any('list', 'ClassiController@list');
        Route::any('info', 'ClassiController@info');
        Route::any('save', 'ClassiController@save');
        Route::any('del', 'ClassiController@del');
    });

    //商品
    Route::prefix('goods')->group(function () {
        Route::any('list', 'GoodsController@list');
        Route::any('info', 'GoodsController@info');
        Route::any('save', 'GoodsController@save');
        Route::any('del', 'GoodsController@del');
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
    });
    Route::prefix('feedback')->group(function () {
        Route::any('list', 'FeedbackController@list');
        Route::any('save', 'FeedbackController@save');
        Route::any('info', 'FeedbackController@info');
        Route::any('del', 'FeedbackController@del');
    });
    Route::prefix('data')->group(function () {
        Route::any('info', 'DataController@info');
        Route::any('total', 'DataController@total');
        Route::any('info/store', 'DataController@infoStore');
    });
    // 微信管理
    Route::prefix('wechat')->group(function () {
        // 获取微信公众号的菜单列表
        Route::any('public/menu/list', 'WeChatController@public_menu_list');
        // 保存公众号菜单列表
        Route::any('public/menu/save', 'WeChatController@public_menu_save');
    });
    Route::prefix('budget')->group(function () {
        // 获取微信公众号的菜单列表
        Route::any('check_list', 'BudgetController@checkList');
        Route::any('success', 'BudgetController@success');
        Route::any('reject', 'BudgetController@reject');
    });
});
