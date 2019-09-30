<?php

if (!function_exists('apiSuccess')) {
    function apiSuccess($data = [], $message = '请求成功！', $error_code = '')
    {
        if (empty($data)) {
            $data = (object)[];
        }
        $responseData = [
            'code' => 1,
            'msg' => $message,
            'data' => $data,
            'error_code' => $error_code,
        ];
        return json($responseData);
    }
}

if (!function_exists('apiError')) {
    function apiError($message = '请求失败！', $error_code = '', $data = [])
    {
        if (empty($data)) {
            $data = (object)[];
        }
        $responseData = [
            'code' => 0,
            'msg' => $message,
            'data' => $data,
            'error_code' => $error_code,
        ];
        return json($responseData);
    }
}