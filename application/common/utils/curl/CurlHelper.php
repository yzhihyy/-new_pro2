<?php

namespace app\common\utils\curl;

class CurlHelper
{
    private $logPath;

    private $logLevel;

    public function __construct()
    {
        // log生成路径
        $this->logPath = config('parameters.curl_log_path');
        // 日志级别
        $this->logLevel = config('parameters.log_level');
    }

    /**
     * curl模拟php post 请求
     *
     * @param string $curlUrl CURL URL
     * @param array $requestParams request parameters
     * @param array $params curl setting parameters
     * @param boolean $postBool is post
     *
     * @return array
     */
    public function curlRequest($curlUrl, $requestParams = [], $params = [], $postBool = true)
    {
        // log生成路径
        $logPath = $this->logPath;
        // 日志级别
        $logLevel = $this->logLevel;

        try {
            // initialize a curl http client
            $ch = curl_init();

            // set curl url
            curl_setopt($ch, CURLOPT_URL, $curlUrl);

            // return executed result
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //不直接输出，返回到变量

            // set header
            if (isset($params['header']) && $params['header']) {
                if ($params['type'] == 'json') {
                    $contentType = 'application/json';
                } else {
                    $contentType = 'application/xml';
                }
                $header = [
                    'Accept:' . $contentType,
                    'Content-Type:' . $contentType . ';charset=utf-8',
                    'Authorization:' . $params['authorization'],
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            }

            // set ssl cert
            if (
                isset($params['sslcert']) && $params['sslcert']
                && isset($params['sslkey']) && $params['sslkey']

            ) {
                curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
                curl_setopt($ch, CURLOPT_SSLCERT, $params['sslcert']);
                curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
                curl_setopt($ch, CURLOPT_SSLKEY, $params['sslkey']);
            }

            // set send data
            if ($postBool) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $requestParams);
            }

            //curl_setopt($ch, CURLOPT_FAILONERROR, true);

            // set whether use ssl http client //忽略证书验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $curlResult = curl_exec($ch);
            //curl请求结果log记录
            $curlResultData = 'url=>' . $curlUrl . '; requestParams=>'
                . (is_array($requestParams) || is_object($requestParams) ? http_build_query($requestParams) : $requestParams)
                . ' ;resultData=' . $curlResult;
            generateCustomLog($curlResultData, $logPath, $logLevel['info']);

            // set error information
            $curlErrno = curl_errno($ch);
            if ($curlErrno) {
                $curlErrmg = curl_error($ch);
                //curl请求错误log记录
                $curlErrmg = 'url=>' . $curlUrl . '; data='
                    . (is_array($requestParams) || is_object($requestParams) ? http_build_query($requestParams) : $requestParams)
                    . ' ;请求错误信息:' . $curlErrmg;
                generateCustomLog($curlResultData, $logPath);
            }
        } catch (\Exception $e) {
            //curl请求异常log记录
            $curlErrmg = 'url=>' . $curlUrl . '; data='
                . (is_array($requestParams) || is_object($requestParams) ? http_build_query($requestParams) : $requestParams)
                . ' ;请求异常信息:' . $e->getMessage();
            generateCustomLog($curlErrmg, $logPath);
        }

        return $curlResult;
    }
}
