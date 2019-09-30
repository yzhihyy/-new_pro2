<?php
/**
 * 需要认证签名的接口路由
 */
Route::group('', function() {
    /**
     * 需要认证登录token的接口路由
     */
    Route::group('', function() {
        // 新增商家相册
        Route::rule('/:version/addMerchantAlbum', 'api/:version.center.Merchant/addMerchantAlbum')->name('api.version.addMerchantAlbum');
        // 新增店铺推荐
        Route::rule('/:version/addMerchantRecommend', 'api/:version.center.Merchant/addMerchantRecommend')->name('api.version.addMerchantRecommend');
        // 新增商家活动
        Route::rule('/:version/addMerchantActivity', 'api/:version.center.Merchant/addMerchantActivity')->name('api.version.addMerchantActivity');
        // 删除商家相册
        Route::rule('/:version/deleteMerchantAlbum', 'api/:version.center.Merchant/deleteMerchantAlbum')->name('api.version.deleteMerchantAlbum');
        // 删除店铺推荐
        Route::rule('/:version/deleteMerchantRecommend', 'api/:version.center.Merchant/deleteMerchantRecommend')->name('api.version.deleteMerchantRecommend');
        // 删除商家活动
        Route::rule('/:version/deleteMerchantActivity', 'api/:version.center.Merchant/deleteMerchantActivity')->name('api.version.deleteMerchantActivity');
        // 保存商家信息
        Route::rule('/:version/saveMerchantInfo', 'api/:version.center.Merchant/saveMerchantInfo')->name('api.version.saveMerchantInfo');
        // 支付完成
        Route::rule('/:version/paymentCompleted', 'api/:version.order.Payment/paymentCompleted')->name('api.version.paymentCompleted');
        // 免单卡列表
        Route::rule('/:version/freeCardList', 'api/:version.center.User/freeCardList')->name('api.version.freeCardList');
        // 免单卡详情
        Route::rule('/:version/freeCardDetail', 'api/:version.center.User/freeCardDetail')->name('api.version.freeCardDetail');

        // 手机登录绑定微信
        Route::rule('/:version/phoneBindWechat', 'api/:version.center.Auth/phoneBindWechat')->name('api.version.phoneBindWechat');

        // 商家-数据-首页
        Route::get('/:version/statisIndex', 'api/:version.center.Information/statisIndex')->name('api.version.statisIndex');
        // 商家-数据-首页-客户统计
        Route::get('/:version/statisConsumeList', 'api/:version.center.Information/statisConsumeList')->name('api.version.statisConsumeList');
        // 商家-数据-首页-今日订单
        Route::get('/:version/statisAllOrder', 'api/:version.center.Information/statisAllOrder')->name('api.version.statisAllOrder');
        // 商家-数据-首页-店铺交易数据
        Route::get('/:version/statisShopData', 'api/:version.center.Information/statisShopData')->name('api.version.statisShopData');
        // 商家-数据-首页-客户详情
        Route::get('/:version/statisCustomerDetail', 'api/:version.center.Information/statisCustomerDetail')->name('api.version.statisCustomerDetail');
        // 商家-数据-首页-订单记录
        Route::get('/:version/statisOrderRecord', 'api/:version.center.Information/statisOrderRecord')->name('api.version.statisOrderRecord');

    })->middleware(app\api\middleware\CheckToken::class);

    /**
     * 不需要认证登录token的接口路由
     */
    Route::group('', function() {
        // 登录
        Route::rule('/:version/login', 'api/:version.center.Auth/login')->name('api.version.login');
        // 微信登录绑定手机
        Route::rule('/:version/wechatBindPhone', 'api/:version.center.Auth/wechatBindPhone')->name('api.version.wechatBindPhone');

        // 首页 - 猜你喜欢
        Route::rule('/:version/joyList', 'api/:version.home.Index/joyList')->name('api.version.joyList');
        // 首页 - 获取店铺列表
        Route::rule('/:version/getShopList', 'api/:version.home.Shop/getShopList')->name('api.version.getShopList');
        // 店铺详情
        Route::rule('/:version/getShopDetail', 'api/:version.home.Shop/getShopDetail')->name('api.version.getShopDetail');
        // 搜索
        Route::rule('/:version/searchShop', 'api/:version.home.Search/shop')->name('api.version.searchShop');

        // 获取店铺活动列表
        Route::rule('/:version/getShopActivityList', 'api/:version.home.Shop/getShopActivityList')->name('api.version.getShopActivityList');
        // 获取店铺活动详情
        Route::rule('/:version/getShopActivityDetail', 'api/:version.home.Shop/getShopActivityDetail')->name('api.version.getShopActivityDetail');
        // 获取店铺推荐列表
        Route::rule('/:version/getShopRecommendList', 'api/:version.home.Shop/getShopRecommendList')->name('api.version.getShopRecommendList');
        // 获取店铺相册列表
        Route::rule('/:version/getShopPhotoAlbumList', 'api/:version.home.Shop/getShopPhotoAlbumList')->name('api.version.getShopPhotoAlbumList');
    });
})->middleware(app\api\middleware\CheckSign::class);

/**
 * 不需要认证签名的接口路由
 */
Route::group('', function() {
    /**
     * 需要认证登录token的接口路由
     */
    Route::group('', function() {
        // 上传商家相册图片
        Route::rule('/:version/uploadMerchantAlbumImg', 'api/:version.center.Upload/uploadMerchantAlbumImg')->name('api.version.uploadMerchantAlbumImg');
        // 上传店铺推荐图片
        Route::rule('/:version/uploadMerchantRecommendImg', 'api/:version.center.Upload/uploadMerchantRecommendImg')->name('api.version.uploadMerchantRecommendImg');
        // 上传商家活动图片
        Route::rule('/:version/uploadMerchantActivityImg', 'api/:version.center.Upload/uploadMerchantActivityImg')->name('api.version.uploadMerchantActivityImg');
    })->middleware(app\api\middleware\CheckToken::class);

    /**
     * 不需要认证登录token的接口路由
     */
    Route::group('', function() {

    });
});