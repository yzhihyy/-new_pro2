<?php

namespace app\api\validate\v3_7_0;

use think\Validate;

class AnchorUserValidate extends Validate
{
    protected $message = [
        'type.require' => '类型不可为空',
        'type.in' => '类型错误',
        'longitude.requireIf' => '经纬度不可为空',
        'longitude.float' => '经纬度格式有误',
        'longitude.between' => '经纬度范围有误',

        'latitude.requireIf' => '经纬度不可为空',
        'latitude.float' => '经纬度格式有误',
        'latitude.between' => '经纬度范围有误',

        //视频直播验证
        'anchor_id.require' => '主播参数错误',
        'anchor_id.number' => '主播格式错误',
        'anchor_id.gt' => '主播数据错误',

        'live_show_id.requireIf' => '视频参数错误',
        'live_show_id.number' => '视频格式错误',
        'live_show_id.gt' => '视频数据错误',

        'live_show_id.require' => '视频不能为空',

        'show_type.require' => '发起视频类型不可为空',
        'show_type.in' => '发起视频类型错误',



    ];

    /**
     * 主播用户列表
     * @return AnchorUserValidate
     */
    public function sceneAnchorUserList()
    {
        return $this->append([
            'type'  => 'require|in:0,1',
            'longitude' => 'requireIf:type,0|float|between:-180,180',
            'latitude' => 'requireIf:type,0|float|between:-180,180',
        ]);
    }

    //用户发起视频
    public function sceneLiveShow()
    {
        return $this->append([
            'anchor_id' => 'require|number|gt:0',
            'action_type' => 'require|in:0,1,2,3,4',
            'live_show_id' => 'requireIf:action_type,1|requireIf:action_type,2|requireIf:action_type,3|requireIf:action_type,4|number|gt:0',
        ]);
    }

    public function sceneMinMeetingPay()
    {
        return $this->append([
            'live_show_id' => 'require|number|gt:0',
        ]);
    }

}
