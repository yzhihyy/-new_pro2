<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class EditVideoValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'title' => ['require', 'max' => 50],
        'video' => ['require'],
        'cover' => ['require'],
        'shop_id' => ['number'],
        'adcode' => ['number'],
        'location' => ['max' => 150],
        'video_width' => ['require', 'max' => 10],
        'video_height' => ['require', 'max' => 10],
        'relation_shop_id' => ['number'],
    ];

    // 错误信息
    protected $message = [
        'title.require' => '标题不可为空',
        'title.max' => '标题长度不可超过50个字符',
        'video.require' => '视频不可为空',
        'cover.require' => '封面不可为空',
        'shop_id.number' => '店铺id格式错误',
        'adcode.number' => '区域code格式错误',
        'location.max' => '地理位置不可超过150个字符',
        'video_width.require' => '视频宽度不可为空',
        'video_width.max' => '视频宽度不可超过10个字符',
        'video_height.require' => '视频高度不可为空',
        'video_height.max' => '视频高度不可超过10个字符',
        'relation_shop_id.number' => '关联店铺id格式错误',
    ];

    // 验证场景
    protected $scene = [
        // 新增视频接口验证规则
        'addVideo' => ['title', 'video', 'cover', 'shop_id', 'adcode', 'location', 'video_width', 'video_height', 'relation_shop_id'],
    ];
}
