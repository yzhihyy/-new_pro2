<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class SearchValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'page' => 'integer|egt:0',
        'keyword' => 'require|max:255',
        'longitude' => 'float|between:-180,180',
        'latitude' => 'float|between:-180,180',
        'type' => 'require|in:1,2,3,4,5'
    ];

    // 错误信息
    protected $message = [
        'page.integer' => '页码错误',
        'page.egt' => '页码错误',

        'keyword.require' => '关键字不可为空',
        'keyword.max' => '关键字不可超过:rule个字符',

        //'longitude.requireIf' => '经纬度不可为空',
        'longitude.float' => '经纬度格式错误',
        'longitude.between' => '经纬度格式错误',

        //'latitude.requireIf' => '经纬度不可为空',
        'latitude.float' => '经纬度格式错误',
        'latitude.between' => '经纬度格式错误',

        'type.require' => '搜索类型不可为空',
        'type.in' => '搜索类型错误',
    ];

    public function sceneSearch()
    {
        return $this;
    }
}
