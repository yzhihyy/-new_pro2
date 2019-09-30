<?php

return [
    // wxpay app支付配置信息
    'wxpay_app_config' => [
        // 应用ID
        'app_id' => 'wx4409d933be74b599',
        // 商户号
        'mch_id' => '1513686191',
        // API密钥
        'key' => 'fujiankuaikuaijiayoukeji2018mskm',
        // 商品描述
        'body' => config('app.app_name'),
        // 日志路径
        'log_path' => '/wxpay/app',
        'sslcert' => Env::get('extend_path') . 'wechat/cert/apiclient_cert.pem',
        'sslkey' => Env::get('extend_path') . 'wechat/cert/apiclient_key.pem',
        // 异步通知URL
        'notify_url' => config('app.app_host').'/paymentWxpayNotify',
        // 2.0.0版本异步通知URL
        'notify_url_v2_0_0' => config('app.app_host').'/user/v2_0_0/paymentWxpayNotify',
        // 3.0.0版本异步通知URL
        'notify_url_v3_0_0' => config('app.app_host').'/user/v3_0_0/wxpayNotify',
        // 3.7.0版本异步通知URL
        'notify_url_v3_7_0' => config('app.app_host').'/user/v3_7_0/wxpayNotify',
    ],

    // wxpay公众号支付配置信息(公众号支付是在商户平台中开通的，所以mchid, key和app支付一样)
    'wxpay_mp_config' => [
        // 应用ID
        'app_id' => 'wxf10829fcbc0073db',
        // 商户号
        'mch_id' => '1513686191',
        // API密钥
        'key' => 'fujiankuaikuaijiayoukeji2018mskm',
        // 商品描述
        'body' => config('app.app_name'),
        // 日志路径
        'log_path' => '/wxpay/mp',
        // 异步通知URL
        'notify_url' => config('app.app_host').'/paymentWxpayNotify',
        // 2.0.0版本异步通知URL
        'notify_url_v2_0_0' => config('app.app_host').'/user/v2_0_0/paymentWxpayNotify',
        // 3.0.0版本异步通知URL
        'notify_url_v3_0_0' => config('app.app_host').'/user/v3_0_0/wxpayNotify',
        // 3.7.0版本异步通知URL
        'notify_url_v3_7_0' => config('app.app_host').'/user/v3_7_0/wxpayNotify',
    ],

    // 微信公众号appid
    'mp_appid' => 'wxf10829fcbc0073db',
    // 微信公众号appid secret
    'mp_app_secret' => 'f37c70d94028c3d75c1d7ccc7dbff313',
    // 支付成功推送的模板消息ID
    'payment_template_message_id' => 'Iz_QzmVFOl3mQAwuQrAu1LkssPkdxtzvcE2e480AeNQ',
    // 微信公众号获取access_token
    'mp_access_token_url' => 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=mp_appid_str&secret=mp_app_secret_str',
    // 微信公众号发送模板消息url
    'mp_send_template_message_url' => 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=access_token_str',
    // 微信公众号授权url
    'mp_signal_url' => 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=mp_appid_str&redirect_uri=redirect_uri_str&response_type=code&scope=snsapi_userinfo&state=state_code#wechat_redirect',
    // 微信公众号获取网页授权access_token的url
    'mp_oauth_access_token_url' => 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=mp_appid_str&secret=mp_app_secret_str&code=code_str&grant_type=authorization_code',
    // 微信公众号刷新网页授权access_token的url
    'mp_oauth_refresh_token_url' => 'https://api.weixin.qq.com/sns/oauth2/refresh_token?appid=mp_appid_str&grant_type=refresh_token&refresh_token=refresh_token_str',
    // 微信公众号拉取用户信息url
    'mp_get_userinfo_url' => 'https://api.weixin.qq.com/sns/userinfo?access_token=access_token_str&openid=openid_str&lang=zh_CN',
    // 微信jssdk获取jsapi_ticket的url
    'mp_jssdk_getticket_url' => 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=access_token_str&type=jsapi',

    // 微信小程序配置
    'wxpay_mini_program_config' => [
        // 小程序 appId
        'appid' => 'wx50b82400960728a7',
        // 小程序 appSecret
        'secret' => '9e92dcd52a2ced0671eb1a1e92434a6e',
        // 根据code换取session
        'code2session_url' => 'https://api.weixin.qq.com/sns/jscode2session?appid=APPID_STR&secret=SECRET_STR&js_code=JSCODE_STR&grant_type=authorization_code',
        // 商户号
        'mch_id' => '1513686191',
        // API密钥
        'key' => 'fujiankuaikuaijiayoukeji2018mskm',
        // 商品描述
        'body' => config('app.app_name'),
        // 日志路径
        'log_path' => '/wxpay/miniapp',
        // 异步通知URL
        'notify_url' => config('app.app_host').'/paymentWxpayNotify',
        // 2.0.0版本异步通知URL
        'notify_url_v2_0_0' => config('app.app_host').'/user/v2_0_0/paymentWxpayNotify',
        // 3.0.0版本异步通知URL
        'notify_url_v3_0_0' => config('app.app_host').'/user/v3_0_0/wxpayNotify',
        // 3.7.0版本异步通知URL
        'notify_url_v3_7_0' => config('app.app_host').'/user/v3_7_0/wxpayNotify',
    ]
];
