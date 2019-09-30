<?php

namespace app\api\middleware;

class CheckSign
{
    public $key = 'a0da4e1c8061fb86';

    /**
     * 校验签名中间件
     *
     * @param \think\Request $request
     * @param \Closure       $next
     *
     * @return mixed|\think\response\Json
     */
    public function handle($request, \Closure $next)
    {
        $platform = $request->header('platform');
        // h5和小程序不验证签名
        if (in_array($platform, [3, 4])) {
            return $next($request);
        }
        $sign = $request->header('sign', null);
        if (empty($sign)) {
            $sign = input('sign');
        }
        if (empty($sign)) {
            $error = config('response.msg14');
            list($code, $msg) = explode('|', $error);
            return apiError($msg, $code);
        }
        // 获取请求参数
        $paramsGet = $request->get();
        $paramsPost = $request->post();
        $params = array_merge($paramsGet, $paramsPost);
        $params = array_slice($params, 1);
        // 剔除sign参数
        unset($params['sign']);
        // 按字典升序排列
        ksort($params);
        // 拼接请求参数
        $temp = [];
        foreach ($params as $k => $v) {
            array_push($temp, $k . '=' . $v);
        }
        $string = join('&', $temp);
     //   echo strtolower(md5($string . $this->key));exit;
        // 校验签名
        if (strtolower(md5($string . $this->key)) == $sign) {
            return $next($request);
        } else {
            $error = config('response.msg15');
            list($code, $msg) = explode('|', $error);
            return apiError($msg, $code);
        }
    }
}
