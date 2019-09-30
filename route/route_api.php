<?php
/**
 * 需要认证签名的接口路由
 */
Route::group('', function() {
    /**
     * 需要认证登录token的接口路由
     */
    Route::group('', function() {
        // 绑定用户的极光ID
        Route::rule('/bindUserRegistrationId', 'api/center.Auth/bindUserRegistrationId')->name('api.bindUserRegistrationId');
        // 准备支付接口
        Route::rule('/preparePayment', 'api/order.Payment/preparePayment')->name('api.preparePayment');
        // 支付接口
        Route::rule('/payment', 'api/order.Payment/payment')->name('api.payment');
        // 获取订单状态
        Route::rule('/getOrderStatus', 'api/order.Payment/getOrderStatus')->name('api.getOrderStatus');

        // 申请商家
        Route::rule('/applyMerchant', 'api/center.Merchant/applyMerchant')->name('api.applyMerchant');
        // 商家余额明细
        Route::rule('/merchantTransactions', 'api/center.Merchant/merchantTransactions')->name('api.merchantTransactions');
        // 商家中心
        Route::rule('/merchantCenter', 'api/center.Merchant/merchantCenter')->name('api.merchantCenter');
        // 免单设置
        Route::rule('/freeSetting', 'api/center.Merchant/freeSetting')->name('api.freeSetting');
        // 商家提现银行卡四元素校验
        Route::post('/merchantBankcard4Verify', 'api/center.Merchant/bankcard4Verify')->name('api.merchantBankcard4Verify');
        // 商家提现银行卡类别查询
        Route::post('/merchantBankcardQuery', 'api/center.Merchant/bankcardQuery')->name('api.merchantBankcardQuery');
        // 商家提现银行卡校验验证码确认
        Route::post('/merchantBankcardCodeVerify', 'api/center.Merchant/bankcardCodeVerify')->name('api.merchantBankcardCodeVerify');
        // 商家提现前信息返回
        Route::get('/merchantWithdrawPreInfo', 'api/center.Merchant/withdrawPreInfo')->name('api.merchantWithdrawPreInfo');
        // 商家提现银行卡更换持卡人
        Route::post('/merchantChangeCardholder', 'api/center.Merchant/changeCardholder')->name('api.merchantChangeCardholder');
        // 商家提现
        Route::post('/merchantWithdraw', 'api/center.Merchant/withdraw')->name('api.merchantWithdraw');
        // 获取商家申请状态
        Route::rule('/getMerchantApplyStatus', 'api/center.Merchant/getMerchantApplyStatus')->name('api.getMerchantApplyStatus');
        // 商家订单列表
        Route::rule('/merchantOrderList', 'api/center.MerchantOrder/index')->name('api.merchantOrderList');
        // 商家客户列表
        Route::rule('/merchantCustomerList', 'api/center.MerchantCustomer/index')->name('api.merchantCustomerList');

        // 用户中心
        Route::rule('/userCenter', 'api/center.User/userCenter')->name('api.userCenter');
        // 我的足迹
        Route::rule('/myFootprint', 'api/center.User/myFootprint')->name('api.myFootprint');
        // 免单列表
        Route::rule('/freeOrderList', 'api/center.User/freeOrderList')->name('api.freeOrderList');
        // 我的订单列表
        Route::rule('/myOrderList', 'api/center.User/myOrderList')->name('api.myOrderList');
        // 修改个人资料
        Route::rule('/saveUserInfo', 'api/center.User/saveUserInfo')->name('api.saveUserInfo');
    })->middleware(app\api\middleware\CheckToken::class);

    /**
     * 不需要认证登录token的接口路由
     */
    Route::group('', function() {
        // 首页
        Route::rule('/', 'api/home.Index/index')->name('api.home');
        // 首页 - 猜你喜欢
        Route::rule('/joyList', 'api/home.Index/joyList')->name('api.joyList');
        // 首页 - 获取店铺列表
        Route::rule('/getShopList', 'api/home.Shop/getShopList')->name('api.getShopList');
        // 店铺详情
        Route::rule('/getShopDetail', 'api/home.Shop/getShopDetail')->name('api.getShopDetail');
        // 登录
        Route::rule('/login', 'api/center.Auth/login')->name('api.login');
        // 获取验证码前置算法
        Route::rule('/codeAlgorithm', 'api/center.Captcha/codeAlgorithm')->name('api.codeAlgorithm');
        // 获取验证码
        Route::rule('/getLoginCaptcha', 'api/center.Captcha/getLoginCaptcha')->name('api.getLoginCaptcha');
        // 搜索
        Route::rule('/searchShop', 'api/home.Search/shop')->name('api.searchShop');
    });
})->middleware(app\api\middleware\CheckSign::class);

/**
 * 不需要认证签名的接口路由
 */
Route::group('', function() {
    // 微信授权
    Route::get('/wxAuth', 'api/basic.Oauth/wxAuth')->name('api.wxAuth');
    // 支付宝付款异步通知接口
    Route::rule('/paymentAlipayNotify', 'api/order.PaymentNotify/paymentAlipayNotify')->name('api.paymentAlipayNotify');
    // 微信付款异步通知接口
    Route::rule('/paymentWxpayNotify', 'api/order.PaymentNotify/paymentWxpayNotify')->name('api.paymentWxpayNotify');
    // 用户协议
    Route::rule('/userProtocol', 'api/webView.WebView/userProtocol')->name('api.userProtocol');
    // 商家协议
    Route::rule('/merchantProtocol', 'api/webView.WebView/merchantProtocol')->name('api.merchantProtocol');
    // 关于我们
    Route::rule('/aboutUs', 'api/webView.WebView/aboutUs')->name('api.aboutUs');
    // 免单说明
    Route::rule('/freeExplain', 'api/webView.WebView/freeExplain')->name('api.freeExplain');
    // APP版本更新
    Route::get('/appVersionUpdate', 'api/basic.Service/appVersionUpdate')->name('api.appVersionUpdate');
    // 用户头像上传接口
    Route::rule('/uploadUserAvatar', 'api/center.Upload/userAvatar')->middleware(app\api\middleware\CheckToken::class)->name('api.uploadUserAvatar');
    // 获取微信授权API
    Route::get('/getWxAuth', 'api/basic.Oauth/getWxAuth')->name('api.getWxAuth');
    // 广告详情
    Route::rule('/bannerDetail', 'api/webView.WebView/bannerDetail')->name('api.bannerDetail');
});
