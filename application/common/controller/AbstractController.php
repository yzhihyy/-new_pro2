<?php

namespace app\common\controller;

use app\common\traits\AbstractTrait;
use app\common\traits\RequestTrait;
use app\common\traits\ResponseTrait;
use think\Controller as ThinkController;

class AbstractController extends ThinkController
{
    use AbstractTrait, RequestTrait, ResponseTrait;

    /**
     * 请求参数校验
     *
     * @param $params
     * @param $ignoreParams
     *
     * @return array|bool
     */
    protected function requestParamsValidate($params, $ignoreParams = [])
    {
        $return = [];
        foreach ($params as $param) {
            $key = 'param.' . $param;
            $value = input($key, null);
            if ($value === null && !in_array($param, $ignoreParams)) {
                return false;
            }
            $return[$param] = $value;
        }
        return $return;
    }
}
