<?php

use think\facade\Route;
use app\api\middleware\CheckSign;
use app\api\middleware\CheckToken;
use app\api\middleware\CheckShopAuthorized;

Route::group('merchant', function() {
    /************************需要认证签名的路由************************/
    Route::group('', function() {
        /************************需要认证登录token的路由************************/
        Route::group('', function() {
            // 申请店铺
            Route::rule(':version/applyShop', 'Shop/applyShop');
            // 商家登录
            Route::post(':version/login', 'Shop/merchantLogin');
            // 切换店铺
            Route::post(':version/switchShop', 'Statistics/switchShop');
            // 店铺详情
            Route::get(':version/getShopDetail', 'Shop/getShopDetail');

            /************************需要认证是否有授权店铺的路由************************/
            Route::group('', function () {
                // 商家中心
                Route::get(':version/center', 'Shop/merchantCenter');
                // 商家余额明细
                Route::get(':version/transactions', 'Shop/merchantTransactions');
                // 商家提现银行卡四元素校验
                Route::post(':version/bankcard4Verify', 'Shop/bankcard4Verify');
                // 商家提现银行卡类别查询
                Route::post(':version/bankcardQuery', 'Shop/bankcardQuery');
                // 商家提现银行卡校验验证码确认
                Route::post(':version/bankcardCodeVerify', 'Shop/bankcardCodeVerify');
                // 商家提现银行卡更换持卡人
                Route::post(':version/changeCardholder', 'Shop/changeCardholder');
                // 商家提现前信息返回
                Route::get(':version/withdrawPreInfo', 'Shop/withdrawPreInfo');
                // 商家提现
                Route::post(':version/withdraw', 'Shop/withdraw');
                // 商家订单列表
                Route::get(':version/orderList', 'Shop/orderList');

                // 申请分店
                Route::rule(':version/applyBranchShop', 'Shop/applyBranchShop');
                // 我的分店
                Route::get(':version/myBranchShop', 'Shop/myBranchShop');
                // 获取免单配置(包含已关联店铺）
                Route::get(':version/getFreeOrderConfiguration', 'Shop/getFreeOrderConfiguration');
                // 免单设置
                Route::post(':version/freeOrderSetting', 'Shop/freeOrderSetting');
                // 添加关联店铺
                Route::rule(':version/addAssociateShop', 'Shop/addAssociateShop');

                // 子账号管理
                Route::get(':version/subAccountManage', 'SubAccount/subAccountManage');
                // 添加子账号 - 检测
                Route::post(':version/detectSubAccount', 'SubAccount/detectSubAccount');
                // 添加子账号 - 验证码校验
                Route::post(':version/subAccountCodeVerify', 'SubAccount/subAccountCodeVerify');
                // 设置子账号备注
                Route::post(':version/setSubAccountRemark', 'SubAccount/setSubAccountRemark');
                // 删除子账号
                Route::post(':version/deleteSubAccount', 'SubAccount/deleteSubAccount');

                // 通知设置
                Route::rule(':version/notificationSetting', 'Shop/notificationSetting');
                // 数据首页
                Route::get(':version/statisIndex', 'Statistics/index');
                // 店铺交易数据
                Route::get(':version/statisShopData', 'Statistics/shopData');
                // 数据-客户列表
                Route::get(':version/customerList', 'Statistics/customerList');
                // 数据-客户详情-消费数据
                Route::get(':version/statisCustomerDetail', 'Statistics/customerDetail');
                // 数据-客户详情-订单记录
                Route::get(':version/statisOrderRecord', 'Statistics/customerOrderRecord');

                // 店铺推荐列表
                Route::get(':version/getRecommendList', 'Shop/getRecommendList');
                // 新增店铺推荐
                Route::post(':version/addRecommend', 'Shop/addRecommend');
                // 删除店铺推荐
                Route::post(':version/deleteRecommend', 'Shop/deleteRecommend');
                // 保存商家信息
                Route::rule(':version/saveInfo', 'Shop/saveInfo');

                // 运营首页
                Route::get(':version/operationIndex', 'Operation/operationIndex');
                // 我的粉丝列表
                Route::get(':version/myFansList', 'Operation/myFansList');
                // 运营 - 客户详情
                Route::get(':version/operationCustomerDetail', 'Operation/customerDetail');
                // 消息首页
                Route::get(':version/messageIndex', 'Message/index');
                // 消息列表
                Route::post(':version/messageList', 'Message/list');

                // 新增视频
                Route::rule(':version/addVideo', 'Publish/addVideo');
                // 新增商家相册
                Route::rule(':version/addMerchantAlbum', 'Publish/addMerchantAlbum');
                // 删除视频
                Route::rule(':version/deleteVideo', 'Publish/deleteVideo');
                // 删除商家相册
                Route::rule(':version/deleteMerchantAlbum', 'Publish/deleteMerchantAlbum');

                // 视频置顶
                Route::get(':version/handleVideoTop', 'Video/handleTop');

                // 获取买单设置
                Route::get(':version/getPaySetting', 'Shop/getPaySetting');
                // 买单设置
                Route::post(':version/paySetting', 'Shop/paySetting');
                // 订单核销
                Route::post(':version/orderWriteOff', 'Shop/orderWriteOff');
                // 获取关联设置
                Route::get(':version/getShopSetting', 'Shop/getShopSetting');
                // 关联设置
                Route::post(':version/shopSetting', 'Shop/shopSetting');
            })->middleware(CheckShopAuthorized::class);
        })->middleware(CheckToken::class);

        /************************不需要认证登录token的路由************************/
        Route::group('', function() {});
    })->middleware(CheckSign::class)->middleware(\app\api\middleware\CheckVersion::class);

    /************************不需要认证签名的路由************************/
    Route::group('', function() {
        /************************需要认证登录token的接口路由************************/
        Route::group('', function() {
            // 上传商家资料图片
            Route::post(':version/uploadProfileImg', 'Shop/uploadMerchantProfileImg');
            // 上传店铺推荐图片
            Route::post(':version/uploadRecommendImg', 'Shop/uploadRecommendImg');
            // 上传店铺LOGO
            Route::post(':version/uploadLogo', 'Shop/uploadMerchantLogo');

            // 上传商家相册图片
            Route::rule(':version/uploadMerchantAlbumImg', 'Shop/uploadMerchantAlbumImg');
        })->middleware(CheckToken::class);
    });
})->prefix('api/:version.merchant.');
