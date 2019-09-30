<?php
//////////////////////////////////////////////////////用户端////////////////////////////////////////////////////////////
Route::group('user', function () {
    // 需要验证签名
    Route::group('', function () {
        // 需要验证token
        Route::group('', function () {
            // 绑定用户的极光ID
            Route::post(':version/bindUserRegistrationId', 'Login/bindUserRegistrationId');

            // 绑定微信
            Route::post(':version/phoneBindWechat', 'Login/phoneBindWechat');
            // 绑定第三方
            Route::post(':version/bindThirdParty', 'Login/bindThirdParty');
            // 解绑第三方
            Route::post(':version/unBindThirdParty', 'Login/unBindThirdParty');

            // 准备支付接口
            Route::rule(':version/preparePayment', 'Order/preparePayment');
            // 支付接口
            Route::post(':version/payment', 'Order/payment');
            // 获取订单状态
            Route::rule(':version/getOrderStatus', 'Order/getOrderStatus');
            // 支付完成
            Route::rule(':version/paymentCompleted', 'Order/paymentCompleted');
            // 免单卡列表
            Route::get(':version/freeCardList', 'User/freeCardList');
            // 免单卡详情
            Route::get(':version/freeCardDetail', 'User/freeCardDetail');

            // 用户中心
            Route::get(':version/userCenter', 'User/userCenter');

            // 我的足迹
            Route::get(':version/myFootprint', 'User/myFootprint');
            // 免单列表
            Route::get(':version/freeOrderList', 'User/freeOrderList');
            // 我的订单列表
            Route::get(':version/myOrderList', 'User/myOrderList');
            // 修改个人资料
            Route::post(':version/saveUserInfo', 'User/saveUserInfo');
            // 分享
            Route::get(':version/share', 'Share/index');

            // 点赞/取消点赞(视频/评论)
            Route::post(':version/doLikeAction', 'Video/doLikeAction');
            // 视频评论/回复
            Route::post(':version/videoComment', 'Video/comment');
            // 视频举报类型
            Route::get(':version/videoReportTypeList', 'Video/videoReportTypeList');
            // 视频举报
            Route::post(':version/videoReport', 'Video/videoReport');
            // 关注首页(v3.4.0以下使用)
            Route::get(':version/followIndex', 'Follow/index');
            // 关注/取消关注
            Route::post(':version/followAction', 'Follow/followAction');

            // 我喜欢的视频列表
            Route::get(':version/getMyJoyVideoList', 'User/getMyJoyVideoList');
            // 我关注的商家列表
            Route::get(':version/getMyFollowShopList', 'User/getMyFollowShopList');
            // 我关注的用户列表
            Route::get(':version/getMyFollowUserList', 'User/getMyFollowUserList');
            // 我的评论回复列表
            Route::get(':version/getMyCommentAndReplyList', 'User/getMyCommentAndReplyList');
            // 我的作品列表
            Route::get(':version/getMyVideoList', 'User/getMyVideoList');
            // 我的粉丝列表
            Route::get(':version/getMyFansList', 'User/getMyFansList');
            // 我的邀请
            Route::get(':version/myInvitation', 'User/myInvitation');

            // 获取我的店铺列表
            Route::get(':version/getMyShopList', 'EditVideo/getMyShopList');
            // 新增视频
            Route::post(':version/addVideo', 'EditVideo/addVideo');
            // 删除视频
            Route::post(':version/deleteVideo', 'EditVideo/deleteVideo');
            // 准备支付接口
            Route::rule(':version/prePayment', 'Payment/prePayment');
            // 支付接口
            Route::post(':version/paying', 'Payment/paying');
            // 支付完成
            Route::rule(':version/finishPayment', 'Payment/finishPayment');

            // 查询好友关系
            Route::post(':version/queryFriendRelationship', 'Contacts/queryFriendRelationship');

            // 视频置顶
            Route::get(':version/handleTop', 'Video/handleTop');

            // 用户举报类型
            Route::get(':version/userReportTypeList', 'User/userReportTypeList');
            // 用户举报
            Route::post(':version/userReport', 'User/userReport');

            // 拉黑&&取消拉黑
            Route::post(':version/handleBlackList', 'User/handleBlackList');

            // 申请合作
            Route::rule(':version/applyCooperation', 'User/applyCooperation');
            // 我的钱包
            Route::get(':version/myWallet', 'Wallet/myWallet');
            // 提现绑定第三方
            Route::post(':version/withdrawBindThirdParty', 'Wallet/withdrawBindThirdParty');
            // 用户提现
            Route::post(':version/withdraw', 'Wallet/withdraw');
            // 用户余额明细
            Route::get(':version/transactions', 'Wallet/transactions');
            // 我的订单
            Route::get(':version/myOrderList', 'User/myOrderList');
            // 使用待核销的订单(预存或预定)
            Route::post(':version/useVerificationOrder', 'Order/useVerificationOrder');

            // 报名参加主题
            Route::post(':version/themeActivitySignUp', 'Theme/signUp');
            // 主题活动投票
            Route::post(':version/themeActivityVote', 'Theme/vote');
            // 主题文章评论点赞
            Route::post(':version/themeArticleCommentLike', 'Theme/articleCommentLike');
            // 主题文章评论
            Route::post(':version/themeArticleComment', 'Theme/articleComment');

            // 首页 - 关注
            Route::get(':version/follow', 'Home/follow');
            // 消息首页
            Route::get(':version/messageIndex', 'Message/index');
            // 消息列表
            Route::post(':version/messageList', 'Message/list');

            // 新增随记
            Route::post(':version/addEssay', 'Essay/addEssay');

            // 保存话题浏览历史
            Route::post(':version/saveTopicViewHistory', 'Topic/saveTopicViewHistory');

            // 申请个人主题
            Route::post(':version/applyTheme', 'User/applyTheme');

            // 预约接口
            Route::rule(':version/appointment', 'Appointment/appointment');
            // 选择店铺接口
            Route::get(':version/selectShop', 'Publish/selectShop');

            // 重置环信用户密码
            Route::post(':version/resetSocialPwd', 'User/resetSocialPwd');

            // 通讯录好友
            Route::post(':version/contactsRelation', 'Contacts/contactsRelation');
            // 获取申请相亲的状态
            Route::get(':version/getApplyBlindDateStatus', 'BlindDate/getApplyBlindDateStatus');
            // 获取我发布的视频列表
            Route::get(':version/getMyPublishVideoList', 'BlindDate/getMyPublishVideoList');
            // 申请相亲
            Route::post(':version/applyBlindDate', 'BlindDate/applyBlindDate');
            // 获取实名认证状态信息
            Route::get(':version/getIdentityCheckStatus', 'BlindDate/getIdentityCheckStatus');
            // 实名认证
            Route::post(':version/identityCheck', 'BlindDate/identityCheck');
            // 我发起的视频记录
            Route::get(':version/iInitiatedVideoRecord', 'BlindDate/iInitiatedVideoRecord');
            // 他人向我发起的视频记录
            Route::get(':version/otherInitiatedVideoRecord', 'BlindDate/otherInitiatedVideoRecord');
            // 准备充值铜板
            Route::get(':version/preRecharge', 'Wallet/preRecharge');
            // 充值铜板
            Route::post(':version/recharge', 'Wallet/recharge');
            // 铜板明细
            Route::get(':version/copperRecordList', 'Wallet/copperRecordList');

            Route::post(':version/liveShow', 'User/liveShow');//发起视频
            Route::post(':version/liveShowMinRequest', 'User/liveShowMinRequest');//每分钟视频请求
            Route::post(':version/copperCoinVerify', 'User/copperCoinVerify');//铜板验证
            Route::post(':version/setMyInviteCode', 'User/setMyInviteCode');//绑定邀请码
            Route::post(':version/bindPushKitDeviceToken', 'User/bindPushKitDeviceToken');//绑定邀请码
        })->middleware(app\api\middleware\CheckToken::class);

        // 无需验证token
        Route::group('', function () {
            // 首页
            Route::get(':version/home', 'Home/index');
            // 首页 - 猜你喜欢
            Route::get(':version/joyList', 'Home/joyList');
            // 首页闪屏图接口
            Route::get(':version/flashShow', 'Home/flashShow');
            // 首页 - 根据店铺分类获取店铺列表
            Route::get(':version/getShopListByCategoryId', 'Shop/getShopListByCategoryId');
            // 搜索
            Route::get(':version/searchShop', 'Search/shop');
            // 首页 - 地区
            Route::get(':version/region', 'Home/region');
            // 获取视频或随记列表
            Route::get(':version/getVideoList', 'Home/getVideoList');

            // 店铺详情
            Route::get(':version/getShopDetail', 'Shop/getShopDetail');
            // 视频转发
            Route::post(':version/doShareAction', 'Video/doShareAction');
            // 视频评论列表
            Route::get(':version/commentList', 'Video/commentList');
            // 视频评论回复列表
            Route::get(':version/commentReplyList', 'Video/commentReplyList');

            // 登录
            Route::post(':version/login', 'Login/index');
            // 获取验证码前置算法
            Route::get(':version/codeAlgorithm', 'Captcha/codeAlgorithm');
            // 获取验证码
            Route::post(':version/getLoginCaptcha', 'Captcha/getLoginCaptcha');
            // 微信登录绑定手机
            Route::post(':version/wechatBindPhone', 'Login/wechatBindPhone');
            // 第三方绑定手机
            Route::post(':version/thirdPartyBindPhone', 'Login/thirdPartyBindPhone');

            // 获取店铺推荐列表
            Route::get(':version/getShopRecommendList', 'Shop/getShopRecommendList');
            // 获取店铺推荐详情
            Route::get(':version/getShopRecommendDetail', 'Shop/getShopRecommendDetail');

            // 获取店铺视频列表
            Route::get(':version/getShopVideoList', 'Shop/getShopVideoList');
            // 获取店铺相册列表
            Route::get(':version/getShopPhotoAlbumList', 'Shop/getShopPhotoAlbumList');
            // 获取视频详情
            Route::get(':version/getVideoDetail', 'Video/getVideoDetail');
            // 获取店铺精讲列表
            Route::get(':version/getShopArticleList', 'Shop/getShopArticleList');

            // 保存视频播放历史
            Route::post(':version/saveVideoPlayHistory', 'Video/savePlayHistory');

            // 用户中心
            Route::get(':version/userCenterInfo', 'UserCenter/index');
            // 获取用户关注的商家列表
            Route::get(':version/userCenterFollowShopList', 'UserCenter/followShopList');
            // 获取用户关注的用户列表
            Route::get(':version/userCenterFollowUserList', 'UserCenter/followUserList');
            // 获取用户的粉丝列表
            Route::get(':version/userCenterFansList', 'UserCenter/fansList');
            // 获取用户的视频列表
            Route::get(':version/userCenterVideoList', 'UserCenter/videoList');

            // 主题活动详情
            Route::get(':version/themeActivityDetail', 'Theme/activityDetail');
            // 主题文章评论列表
            Route::get(':version/themeArticleCommentList', 'Theme/articleCommentList');
            // 主题文章详情
            Route::get(':version/themeArticleDetail', 'Theme/articleDetail');
            // 领取投票分享红包
            Route::post(':version/themeReceiveBonus', 'Theme/receiveBonus');
            // 主题列表
            Route::get(':version/themeList', 'Theme/themeList');
            // 主题列表-进行中
            Route::get(':version/themeIngList', 'Theme/themeIngList');
            // 主题参与商家
            Route::get(':version/themeShopList', 'Theme/themeShopList');
            // 搜索页3个主题
            Route::get(':version/searchPageThemeList', 'Theme/searchPageThemeList');

            // 店铺信息
            Route::get(':version/getShopInfo', 'Shop/getShopInfo');
            // 搜索店铺接口
            Route::get(':version/publishSearchShop', 'Publish/searchShop');

            // 他关注的用户列表
            Route::get(':version/getUserFollowUserList', 'User/getUserFollowUserList');
            //相亲首页
            Route::get(':version/indexBannerInfo', 'User/indexBannerInfo');
            Route::get(':version/meetingIndex', 'User/meetingIndex');
            Route::get(':version/anchorVideoDetail', 'Video/anchorVideoDetail');

            // 用户主页
            Route::get(':version/userHome', 'User/home');
        });
    })->middleware(app\api\middleware\CheckSign::class)->middleware(app\api\middleware\CheckVersion::class);

    // 无需验证签名
    Route::group('', function () {
        // 需要验证token
        Route::group('', function () {
            // 上传身份证件照片
            Route::post(':version/uploadIdentityImg', 'BlindDate/uploadIdentityImg');
        })->middleware(app\api\middleware\CheckToken::class);

        // 无需验证token
        Route::group('', function () {
            // 用户协议
            Route::get(':version/userProtocol', 'WebView/userProtocol');
            // 商家协议
            Route::get(':version/merchantProtocol', 'WebView/merchantProtocol');
            // 关于我们
            Route::get(':version/aboutUs', 'WebView/aboutUs');
            // 免单说明
            Route::get(':version/freeExplain', 'WebView/freeExplain');
            // 广告详情
            Route::get(':version/bannerDetail', 'WebView/bannerDetail');
            // APP版本更新
            Route::get(':version/appVersionUpdate', 'Service/appVersionUpdate');
            // 微信jssdk分享接口
            Route::get(':version/wxJssdkShare', 'WxJssdk/share');
            // 获取微信网页授权
            Route::get(':version/getWxAuth', 'Oauth/getWxAuth');
            // 获取小程序授权
            Route::get(':version/miniProgramGetInfoByCode', 'Oauth/miniProgramGetInfoByCode');
            // 支付宝付款异步通知接口
            Route::rule(':version/paymentAlipayNotify', 'Order/alipayNotify');
            // 微信付款异步通知接口
            Route::rule(':version/paymentWxpayNotify', 'Order/wxpayNotify');
            // 支付宝付款异步通知接口
            Route::rule(':version/alipayNotify', 'Payment/alipayNotify');
            // 微信付款异步通知接口
            Route::rule(':version/wxpayNotify', 'Payment/wxpayNotify');
            // 苹果付款app通知接口
            Route::rule(':version/applePayNotify', 'Payment/applePayNotify');
            // 获取音乐列表
            Route::get(':version/getMusicList', 'EditVideo/getMusicList');
            // 用户头像上传接口
            Route::post(':version/uploadUserAvatar', 'Upload/userAvatar');
            // 主题文章和活动列表（发现-推荐页面）
            Route::get(':version/themeArticleAndActivityList', 'ThemeArticle/themeArticleAndActivityList');

            // 随记列表
            Route::get(':version/essayAndVideoList', 'Essay/essayAndVideoList');
            // 用户封面上传接口
            Route::post(':version/uploadUserCover', 'Upload/userCover');
        });
    });
})->prefix('api/:version.user.');
