<?php

namespace app\common\utils\sms;

/**
 * 短信发送助手
 *
 * Class CaptchaHelper
 *
 * @package app\common\utils\sms
 */
class CaptchaHelper
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \TopClient
     */
    private $topClient;

    /**
     * @var \AlibabaAliqinFcSmsNumSendRequest
     */
    private $sendRequest;

    /**
     * CaptchaHelper constructor.
     *
     */
    public function __construct()
    {
        // 短信参数配置
        $this->config = config('aliyunCaptcha.');
    }

    /**
     * 初始化短信发送助手
     */
    private function initCaptchaTopClient()
    {
        if (empty($this->topClient) || empty($this->sendRequest)) {
            // SDK引入
            include(__DIR__ . '/taobao-sdk/TopSdk.php');
            $appKey = $this->config['appKey'];
            $secretKey = $this->config['secretKey'];
            $this->topClient = new \TopClient($appKey, $secretKey);
            $this->sendRequest = new \AlibabaAliqinFcSmsNumSendRequest;
        }
    }

    /**
     * @发送验证码
     * @param array $params 验证码配置参数
     * @return boolean
     */
    public function sendAlibabaCaptcha($params)
    {
        // 发送验证码的手机号
        $recNum = $params['phone'];
        try {
            // 初始化短信发送助手
            $this->initCaptchaTopClient();
            // 发送短信
            $this->sendRequest->setSmsType($this->config['smsType']);
            $this->sendRequest->setSmsFreeSignName($this->config['smsFreeSignName']);//短信模板签名
            $this->sendRequest->setSmsParam($params['smsParam']);
            $this->sendRequest->setRecNum($recNum);
            $this->sendRequest->setSmsTemplateCode($params['smsTemplateCode']);
            $result = $this->topClient->execute($this->sendRequest);

            // 短信验证码第三方返回的信息log记录
            $logContent = "短信验证码第三方返回信息：phone={$recNum}, code={$result->children()->code}, msg={$result->children()->msg}, error_code={$result->children()->result->err_code}";
            generateApiLog($logContent, 'info');

            // 短信发送失败
            if($result->children()->result->err_code != 0 || !empty($result->children()->code) ) {
                $logContent = "{$recNum} 短信验证码异常：" . $result->children()->sub_msg;
                generateApiLog($logContent);
            }

            return $result;
        } catch (\Exception $e) {
            $logContent = $recNum . '短信验证码异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return false;
    }
}
