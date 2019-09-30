<?php

namespace app\api\controller\center;

use app\api\Presenter;

class Captcha extends Presenter
{
    /**
     * 获取验证码
     *
     * @return \think\response\Json
     */
    public function getLoginCaptcha()
    {
        /**
         * @var \app\api\model\Captcha $captchaModel
         */
        if ($this->request->isPost()) {
            try {
                // 校验手机号
                $paramsArray = input();
                $validate = validate('api/Captcha');
                $checkResult = $validate->scene('getLoginCaptcha')->check($paramsArray);
                if (!$checkResult) {
                    $errorMsg = $validate->getError();
                    return apiError($errorMsg);
                }
                // 判断该手机号当天发送次数是否超出限制
                $captchaModel = model('api/Captcha');
                $countInfo = $captchaModel->getCountToday([
                    'phone' => $paramsArray['phone']
                ]);
                $captchaLimitPerDay = config('parameters.captchaLimitPerDay');
                if ($countInfo['countNum'] >= $captchaLimitPerDay) {
                    return apiError(config('response.msg8'));
                }
                // 判断该手机号发送频率是否超出限制
                $captchaLimitTimeInterval = config('parameters.captchaLimitTimeInterval');
                $lastSendTime = $captchaModel->getLastSendTime([
                    'phone' => $paramsArray['phone']
                ]);
                if ($lastSendTime) {
                    $timeDiff = time() - strtotime($lastSendTime);
                    if ($timeDiff < $captchaLimitTimeInterval) {
                        return apiError(config('response.msg8'));
                    }
                }
                // 前置算法验证
                if ($this->request->header('platform') != 3) {
                    $evalResult = getCustomCache($paramsArray['code_token']);
                    if (empty($evalResult) || $evalResult != $paramsArray['code_content']) {
                        return apiError(config('response.msg16'));
                    }
                }
                // 发送短信验证码
                $code = generateTelCode();
                $data = [
                    'phone' => $paramsArray['phone'],
                    'captcha' => $code,
                    'smsParam' => json_encode(['code' => $code]),
                    'smsTemplateCode' => config('aliyunCaptcha.loginTemplateId')
                ];
                $result = $this->sendCaptcha($data);
                if ($result !== true) {
                    return apiError($result);
                }
                // 保存验证码
                $time = time();
                $expireTime = $time + config('parameters.captchaExpireTime');
                $result = $captchaModel->save([
                    'type' => 1,
                    'phone' => $paramsArray['phone'],
                    'code' => $code,
                    'expire_time' => date('Y-m-d H:i:s', $expireTime),
                    'generate_time' => date('Y-m-d H:i:s', $time)
                ]);
                if ($result) {
                    return apiSuccess();
                } else {
                    return apiError();
                }
            } catch (\Exception $e) {
                $logContent = '获取登录验证码接口异常信息：' . $e->getMessage();
                generateApiLog($logContent);
                return apiError(config('response.msg5'));
            }
        }
        return apiError(config('response.msg4'));
    }

    /**
     * 获取验证码前置算法
     *
     * @return \think\response\Json
     */
    public function codeAlgorithm()
    {
        try {
            // 操作符
            $operate = [1 => '+', 2 => '-', 3 => '*'];
            // 生成左操作数
            $leftOperand = mt_rand(1, 10000);
            // 生成右操作数
            $rightOperand = mt_rand(1, 10000);
            // 随机获取一个操作符
            $operateRand = array_rand($operate);
            // 生成表达式
            $expression = $leftOperand . ',' . $operateRand . ',' . $rightOperand;
            // 生成值
            $evalResult = eval("return {$leftOperand} {$operate[$operateRand]} {$rightOperand};");
            // 生成缓存
            $key = uniqid(date('YmdHis'), true);
            setCustomCache($key, $evalResult);
            // 返回表达式和缓存key
            $responseData = [
                'code_content' => $expression,
                'code_token' => $key
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取登录验证码接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}
