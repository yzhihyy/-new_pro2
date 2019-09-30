<?php

namespace app\api\validate\v3_7_0;

use think\Validate;

class AnchorVideoValidate extends Validate
{
    protected $message = [
        'video_id.require' => '视频参数不可为空',
        'video_id.number' => '视频参数错误',
        'video_id.gt' => '视频参数范围错误',

        'longitude.require' => '经纬度不可为空',
        'longitude.float' => '经纬度格式有误',
        'longitude.between' => '经纬度范围有误',

        'latitude.require' => '经纬度不可为空',
        'latitude.float' => '经纬度格式有误',
        'latitude.between' => '经纬度范围有误',
    ];

    public function sceneAnchorVideo()
    {
        return $this->append([
            'video_id'  => 'require|number|gt:0',
            'longitude' => 'require|float|between:-180,180',
            'latitude' => 'require|float|between:-180,180',
        ]);
    }

}
