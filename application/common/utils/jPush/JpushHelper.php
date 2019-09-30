<?php

namespace app\common\utils\jPush;

use JPush\Client as JPush;

class JpushHelper
{
    private static $apnsProduction; // true表示推送生产环境，false表示推送开发环境，如果不指定则默认为推送生产环境
    private static $appKey;
    private static $masterSecret;

    /**
     * 初始化配置
     */
    private static function initConfig()
    {
        $isDebug = config('app_debug');
        self::$apnsProduction = !$isDebug;
        self::$appKey = config('jPush.appKey');
        self::$masterSecret = config('jPush.masterSecret');
    }

    /**
     * 根据极光ID批量推送(通知 + 自定义消息)
     *
     * @param array|string $registrationIdArray
     * @param array $params
     *
     * @return array|bool
     */
    public static function push($registrationIdArray, $params)
    {
        self::initConfig();
        $client = new JPush(self::$appKey, self::$masterSecret);
        try {
            $title = $params['title'] ?? 'Hi, JPush';
            $message = $params['message'] ?? 'message content';
            $contentType = $params['contentType'] ?? 'messageType';
            $sound = $params['sound'] ?? '';
            $extras = $params['extras'] ?? [];
            // 推送
            $response = $client->push()
                ->setPlatform('all')
                ->addRegistrationId($registrationIdArray)
                ->androidNotification($message, ['extras' => $extras])
                ->iosNotification($message, ['sound' => $sound, 'mutable-content' => true, 'extras' => $extras])
                ->message($message, [
                    'title' => $title,
                    'content_type' => $contentType,
                    'extras' => $extras
                ])
                ->options(['apns_production' => self::$apnsProduction])
                ->send();
            // 记录日志
            $logContent = '极光推送返回信息：' . json_encode($response);
            generateApiLog($logContent, 'info');
            return $response;
        } catch (\Exception $e) {
            $logContent = '极光推送异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return false;
        }
    }

    /**
     * 根据极光ID推送自定义消息
     *
     * @param array|string $registrationIdArray
     * @param array $params
     *
     * @return array|bool
     */
    public static function message($registrationIdArray, $params)
    {
        self::initConfig();
        $client = new JPush(self::$appKey, self::$masterSecret);
        try {
            $title = $params['title'] ?? 'Hi, JPush';
            $message = $params['message'] ?? 'message content';
            $contentType = $params['contentType'] ?? 'messageType';
            $extras = $params['extras'] ?? [];
            // 推送
            $response = $client->push()
                ->setPlatform('all')
                ->addRegistrationId($registrationIdArray)
                ->message($message, [
                    'title' => $title,
                    'content_type' => $contentType,
                    'extras' => $extras
                ])
                ->options(['apns_production' => self::$apnsProduction])
                ->send();
            // 记录日志
            $logContent = '极光自定义消息返回信息：' . json_encode($response);
            generateApiLog($logContent, 'info');
            return $response;
        } catch (\Exception $e) {
            $logContent = '极光自定义消息异常信息：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return false;
    }
}
