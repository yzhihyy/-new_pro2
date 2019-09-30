<?php

namespace app\api\validate\v3_7_0;

use think\Validate;

class BlindDateValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'contact' => ['require', 'max' => 5],
        'gender' => ['require', 'in' => '1,2'],
        'phone' => ['require', 'max' => 11],
        'code' => ['require'],
        'adcode' => ['require', 'number'],
        'location' => ['require', 'max' => 150],
        'longitude' => ['require'],
        'latitude' => ['require'],
        'video_id' => ['require'],

        'real_name' => ['require'],
        'id_number' => ['require'],
        'identity_card_holder_half_img' => ['require'],
        'identity_card_back_face_img' => ['require'],
    ];

    // 错误信息
    protected $message = [
        'contact.require' => '请填写联系人',
        'contact.max' => '联系人不可超过5个字符',
        'gender.require' => '请填写性别',
        'gender.in' => '性别格式错误',
        'phone.require' => '请填写手机号',
        'phone.max' => '手机号格式错误',
        'code.require' => '验证码不能为空',
        'adcode.require' => '区域code不能为空',
        'adcode.number' => '区域code格式错误',
        'location.require' => '地理位置不可为空',
        'location.max' => '地理位置不可超过150个字符',
        'longitude.require' => '经度不可为空',
        'latitude.require' => '纬度不可为空',
        'video_id.require' => '请上传视频',

        'real_name.require' => '请填写真实姓名',
        'id_number.require' => '请填写身份证号码',
        'identity_card_holder_half_img.require' => '请上传手持人像面半身照',
        'identity_card_back_face_img.require' => '请上传国徽面',
    ];

    // 验证场景
    protected $scene = [
        // 申请相亲接口验证规则
        'applyBlindDate' => ['contact', 'gender', 'phone', 'code', 'adcode', 'location', 'longitude', 'latitude', 'video_id'],
        // 实名认证接口验证规则
        'identityCheck' => ['real_name', 'id_number', 'identity_card_holder_half_img', 'identity_card_back_face_img'],
    ];
}