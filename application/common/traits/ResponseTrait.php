<?php

namespace app\common\traits;

trait ResponseTrait
{
    /**
     * ajax错误响应
     *
     * @param string $msg
     * @param array  $data
     *
     * @return \think\response\Json
     */
    public function ajaxError($msg = 'FAILED', $data = [])
    {
        $responseData = [
            'status' => 0,
            'msg' => $msg,
            'data' => $data
        ];
        return json($responseData);
    }

    /**
     * ajax成功响应
     *
     * @param array  $data
     * @param string $msg
     *
     * @return \think\response\Json
     */
    public function ajaxSuccess($data = [], $msg = 'SUCCESS')
    {
        $responseData = [
            'status' => 1,
            'msg' => $msg,
            'data' => $data
        ];
        return json($responseData);
    }
}
