<?php
return [
    //当前版本号
    'current_version' => '3.7.0',
    // 分页大小配置
    'page_size_level_1' => 10,
    'page_size_level_2' => 20,
    'page_size_level_3' => 30,
    'page_size_level_4' => 40,
    'page_size_level_5' => 50,

    // 短信验证码每天发送数量限制
    'captchaLimitPerDay' => 10,
    // 短信验证码发送频率(单位s)
    'captchaLimitTimeInterval' => 60,
    // 短信验证码过期时间
    'captchaExpireTime' => 86400,

    // curl log生成路径
    'curl_log_path' => '/curl',
    // 订单 log生成路径
    'order_log_path' => '/order',
    // 日志级别
    'log_level' => [
        'debug' => 'debug',
        'info' => 'info',
        'notice' => 'notice',
        'warning' => 'warning',
        'error' => 'error',
        'critical' => 'critical',
        'alert' => 'alert',
        'emergency' => 'emergency'
    ],

    // 银行和第三方支付图标
    'payment_icon' => [
        'ALIPAY' => '/paymentIcon/ALIPAY.png',
        'WECHAT' => '/paymentIcon/WECHAT.png',
        'BALANCE' => '/paymentIcon/BALANCE.png',
        '中国银行' => '/paymentIcon/BOC.png',
        '中国建设银行' => '/paymentIcon/CCB.png',
        '中国工商银行' => '/paymentIcon/ICBC.png',
        '中国农业银行' => '/paymentIcon/ABC.png',
        '中国民生银行' => '/paymentIcon/CMBC.png',
        '中国邮政储蓄银行' => '/paymentIcon/PSBC.png',
        '兴业银行' => '/paymentIcon/CIB.png',
        '招商银行' => '/paymentIcon/CMB.png',
    ],

    // 新注册的用户默认头像图片路径
    'new_register_user_avatar' => '/userHeadimg/default.jpg',
    // 店铺收款码缓存名
    'shop_qrcode_cache_name' => 'qrcode_info_%d',
    // 店铺收款码目录名
    'shop_receipt_qrcode_img_dir' => '/shopReceiptQrcodeImg',
    // 店铺收款码背景图
    'shop_receipt_qrcode_bg_name' => '/receipt_bg.png',

    // 图片的扩展名
    'image_ext' => [
        'gif',
        'png',
        'jpg'
    ],
    // 图片的mime
    'image_mime' => [
        'image/gif',
        'image/png',
        'image/jpeg'
    ],
    // 图片上传大小限制(单位:M)
    'image_max_size' => 2,
    // 图片默认水印
    'image_default_water' => '/water.png',

    // 用户头像上传目录
    'user_avatar_upload_path' => '/userAvatar',
    // 用户头像大小限制（单位M）
    'user_avatar_max_size' => 3,
    // 用户头像缩略图
    'user_thumb_avatar_size' => [100, 100],

    // 用户封面上传目录
    'user_cover_upload_path' => '/userCover',
    // 用户封面大小限制（单位M）
    'user_cover_max_size' => 3,
    // 用户封面缩略图
    'user_thumb_cover_size' => [375, 300],

    #身份证号正则表达式
    'idcard_number_regex' => '/^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/i',

    // 版本更新配置
    'user_app_version' => [
        'update_url' => ['ios' => '', 'android' => 'http://www.kemiandan.com/apk/kkmd.apk'],
        'new_version' => '0.0.0',
        'update_type' => 1, // 更新类型 0：不用更新 1:建议更新 2:强制更新
        'update_info' => '修复已知bug'
    ],

    // 最低支付金额
    'min_payment_money' => 0.01,

    // 上传大小限制（单位M）
    'upload_size_level_3' => 3,
    // 图片缩略图大小
    'img_thumb_size_level_3' => [300, 300],

    // 上传图片张数限制
    'upload_max_count_level_9' => 9,

    // 客服电话
    'customer_service_phone' => '0594-2283888',

    // 首页视频显示规则(系统推荐5个 + 用户喜好3个 + 普通视频2个)
    'home_video_display_rule' => [5, 3, 2],
    // APP审核时的首页视频显示规则
    'home_video_display_rule_for_review' => [
        'enable_flag' => false,  // true启用, false关闭
        'platform' => [1],      // 平台 1：iOS, 2：Android, 3：H5
        'version' => '3.5.0',   // 低于指定版本
        'tagNames' => ['汽车'],
    ],
    // APP审核时的店铺的视频列表是否返回
    'shop_detail_video_display_rule_for_review' => [
        'enable_flag' => false,  // true启用, false关闭
        'platform' => [1],      // 平台 1：iOS, 2：Android, 3：H5
        'version' => '3.5.0',   // 指定版本
    ],
    //平台主题文章ios审核时关闭
    'article_display_rule_for_review' => [
        'ids' => [44, 26, 19],// 平台主题文章id
        'enable_flag' => false,  // true启用, false关闭
        'platform' => [1],      // 平台 1：iOS, 2：Android, 3：H5
        'version' => '3.5.0',   // 低于指定版本
    ],
    //个人主题ios审核时关闭
    'activity_display_rule_for_review' => [
        'ids' => [21, 25],// 主题id
        'enable_flag' => false,  // true启用, false关闭
        'platform' => [1],      // 平台 1：iOS, 2：Android, 3：H5
        'version' => '3.5.0',   // 低于指定版本
    ],

    // 是否苹果审核状态 1是 0否 （注意：进入苹果审核时要记得开！！！！！！！！！！！！！！！！！！）
    'is_apple_review_status' => 1,
    // 非苹果审核状态下的苹果支付开关，1开 0关
    'not_in_review_status_apple_pay_flag' => 0,

    // 首页话题显示规则(系统推荐5个 + 普通话题2个)
    'home_topic_display_rule' => [5, 5],

    // 通讯录好友关系查询个数限制
    'contacts_friend_relationship_query_limit' => 20,

    // 用户身份证图片上传目录
    'user_identity_card_image_upload_path' => '/userIdentityCard',

    /********************商家上传资料图片配置********************/
    // 身份证图片上传目录
    'identity_card_image_upload_path' => '/identityCard',
    // 身份证图片大小限制(单位M)
    'identity_card_image_max_size' => 3,

    /********************商家端配置********************/
    // 商家LOGO图片上传目录
    'merchant_image_upload_path' => '/shopImage',
    // 商家相册店铺照片上传目录
    'merchant_album_upload_path' => '/merchantAlbum',
    // 商家推荐商品图片上传目录
    'merchant_recommend_upload_path' => '/merchantRecommend',
    // 店铺活动图片上传目录
    'merchant_activity_upload_path' => '/merchantActivity',

    // 身份证图片上传目录
    'merchant_identity_card_image_upload_path' => '/identityCard',
    // 身份证图片大小限制(单位M)
    'merchant_identity_card_image_max_size' => 3,
    // 店铺默认LOGO
    'merchant_shop_default_logo_img' => '/shopImage/default.png',
    // 店铺状态描述 - online_status
    'merchant_status_text' => ['已下线', '已上线', '申请中', '已驳回'],
    // 店铺添加子账号时的默认权限(提现菜单/提现前信息返回/提现)
    'merchant_sub_account_default_rule' => '10,14,16',
    // 限制版本号以下提示错误
    'low_version' => [
        'enable_flag' => false,
        'version' => '3.2.0',
        'platform' => [1, 2],      // 平台 1：iOS, 2：Android, 3：H5
    ],
];
