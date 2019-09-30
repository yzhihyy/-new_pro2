<?php

namespace app\common\utils\bankcard;

/**
 * Bankcard 查询、认证助手,扩展类
 */
class BankcardHelper
{
    // 配置信息
    private $config;

    // 日志路径
    private $logPath;

    /**
     * BankcardHandler constructor.
     */
    public function __construct()
    {
        $this->config = config('juhe.juhe_platform_api');
        $this->logPath = config('juhe.juhe_platform_api.log_path');
    }

    /**
     * 银行卡类别查询
     *
     * @param array $params 验证银行卡输入的参数
     *
     * @return array|bool
     */
    public function bankcardQuery($params)
    {
        try {
            $url = $this->config['bankcardsilk_url'];
            $requestParams = [
                'key' => $this->config['bankcardsilk_key'],
                'num' => $params['bankcard_num']
            ];
            $result = $this->request($url, $requestParams);
            $resultArray = json_decode($result, true);
            // 判断是否请求成功
            if (isset($resultArray['error_code']) && $resultArray['error_code'] == 0) {
                $logContent = "银行卡类别查询信息：bankcardNum={$params['bankcard_num']}, userId={$params['user_id']}, 聚合数据返回结果信息：{$result}";
                generateCustomLog($logContent, $this->logPath, 'info');
                $resultArray['result']['logo'] = isset($resultArray['result']['logo']) ?
                    $this->config['bankcard_logo_url'] . $resultArray['result']['logo'] : '';
            } else {
                $logContent = "银行卡类别查询失败, bankcardNum={$params['bankcard_num']}, userId={$params['user_id']}, 聚合数据返回结果信息：{$result}";
                generateCustomLog($logContent, $this->logPath);
            }

            return $resultArray;
        } catch (\Exception $e) {
            $logContent = "银行卡类别查询出现异常：" . $e->getMessage() . ", bankcardNum={$params['bankcard_num']}, userId={$params['user_id']}";
            generateCustomLog($logContent, $this->logPath);
        }

        return false;
    }

    /**
     * 银行卡四元素校验
     *
     * @param array $params 验证银行卡输入的参数
     * @return array|boolean
     */
    public function bankcardVerify4($params)
    {
        try {
            $url = $this->config['verifybankcard4_url'];
            $requestParams = [
                'key' => $this->config['verifybankcard4_key'],
                'bankcard' => $params['bankcard_num'],
                'mobile' => $params['phone'],
                'realname' => $params['holder_name'],
                'idcard' => $params['identity_card_num']
            ];
            $result = $this->request($url, $requestParams);
            $resultArray = json_decode($result, true);
            // 判断是否请求成功
            if (isset($resultArray['error_code']) && $resultArray['error_code'] == 0) {
                $logContent = "银行卡四元素校验信息, 请求信息：" . serialize($params) . ", userId={$params['user_id']}, 聚合数据返回结果信息：{$result}";
                generateCustomLog($logContent, $this->logPath, 'info');
            } else {
                $logContent = "银行卡四元素校验失败, 请求信息：" . serialize($params) . ", userId={$params['user_id']}, 聚合数据返回结果信息：{$result}";
                generateCustomLog($logContent, $this->logPath);
            }

            return $resultArray;
        } catch (\Exception $e) {
            $logContent = "银行卡四元素校验出现异常：" . $e->getMessage() . ", 请求信息：" . serialize($params) . ", userId={$params['user_id']}";
            generateCustomLog($logContent, $this->logPath);
        }
        return false;
    }

    /**
     * Send request.
     *
     * @param string $url
     * @param array $params 参数数组
     * @param string $method
     *
     * @return bool|mixed
     */
    private function request($url, $params = [], $method = 'GET')
    {
        $ch = curl_init();
        switch ($method) {
            case 'GET':
                $url .= '?' . http_build_query($params);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                break;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);
        $aStatus = curl_getinfo($ch);
        curl_close($ch);
        if (intval($aStatus['http_code']) == 200) {
            return $result;
        }

        return false;
    }
}
