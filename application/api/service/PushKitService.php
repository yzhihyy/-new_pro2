<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/11
 * Time: 16:24
 */

namespace app\api\service;

class PushKitService
{

    public function __construct()
    {

    }

    //iOS10之后，才支持title,subtitle
    public static function push($deviceToken = '', $message, $params = [])
    {
        return self::push_message($deviceToken, $message, '', '', $params);
    }

    public static function push_title($deviceToken = '', $message, $title)
    {
        return self::push_message($deviceToken, $message, $title, '');
    }

    public static function push_message($deviceToken = '', $message = '', $title = '', $subtitle = '', $params = [])
    {
        $aps = array(
            "alert" => array(
                "title" => $title,
                "subtitle" => $subtitle,
                "body" => $message,
                "nickname" => $params['nickname'] ?? '',
            ),
            'sound' => 'default', #$sound = "ping1.caf";
            'badge' => 1
        );
        return self::push_apns($deviceToken, $aps);
    }

    public static function push_apns($deviceToken = '', $aps = array('alert' => '', 'sound' => 'default', 'badge' => 0))
    {
        if (strlen($deviceToken) <= 0) {
            return 'deviceToken 长度错误';
        }

        $pass = config('pushKit.secret');//密码必须是证书的密码
        $pemPath = config('pushKit.pem_path');

        /* End of Configurable Items */
        $ctx = stream_context_create();
        // $ctx = stream_context_create([
        //  'ssl'=>[
        //      'verify_peer'=>false,
        //      'verify_peer_name'=>false
        //  ]
        // ]);

        // anps_dev_club是在同文件夹下的pem证书(配置证书)
        stream_context_set_option($ctx, 'ssl', 'local_cert', $pemPath);
        // assume the private key passphase was removed.（输入密码）
        stream_context_set_option($ctx, 'ssl', 'passphrase', $pass);
        // ssl://gateway.sandbox.push.apple.com:2195 这个是苹果开发测试地址
        // ssl://gateway.push.apple.com:2195 苹果发布运行地址
        // $apnsHost='tls://gateway.sandbox.push.apple.com:2195';
        $apnsHost = 'tls://gateway.sandbox.push.apple.com:2195';
        #好像这个用发布和调试都可以
        $fp = stream_socket_client($apnsHost, $err, $errstr, 60, STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT, $ctx);
        #发布
        // $fp = stream_socket_client($apnsHost, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);

        if (!$fp) {
            generateApiLog("Failed to connect $err $errstr");
            return '连接失败';
        }

        // Construct the notification payload
        // array() php的数组和字典
        // $body['aps'] = array(
        //  'alert' => '推送测试！',#推送的消息
        //  'sound' => 'default', #$sound = "ping1.caf";
        //  'badge' => 4
        // );
        //

        $body['aps'] = $aps;
        # 把字典转化成 json字符串
        $payload = json_encode($body);
        // 这是去掉空格，什么的，因为token里面含有一些不用的符号
        $msg = chr(0) . pack("n", 32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n", strlen($payload)) . $payload;

        // print "sending message :" . $payload . "n".$msg;
        // 发生推送
        $result = fwrite($fp, $msg, strlen($msg));
        fclose($fp);
        if (!empty($result)) {
            generateCustomLog($fp, '', 'info');
            return true;
        }
        generateApiLog("发送失败 $result");
        return '发送失败';
    }
}