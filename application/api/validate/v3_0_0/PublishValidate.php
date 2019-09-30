<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class PublishValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'image' => ['require', 'max' => 255],
        'name' => ['require', 'max' => 10],

        'content' => ['require', 'max' => 200],
        'image_list' => ['require'],

        'title' => ['require', 'max' => 50],
        'video' => ['require'],
        'cover' => ['require'],
        'province_id' => ['number'],
        'city_id' => ['number'],
        'area_id' => ['number'],
        'video_width' => ['require', 'max' => 10],
        'video_height' => ['require', 'max' => 10],

        'photo_id' => ['require'],
    ];

    // 错误信息
    protected $message = [
        'image.require' => '图片不可为空',
        'image.max' => '图片长度不可超过255个字符',
        'name.require' => '图片名称不可为空',
        'name.max' => '图片名称不可超过10个字符',

        'content.require' => '内容不可为空',
        'content.max' => '内容不可超过200个字符',
        'image_list.require' => '图片列表不可为空',

        'title.require' => '标题不可为空',
        'title.max' => '标题长度不可超过50个字符',
        'video.require' => '视频不可为空',
        'cover.require' => '封面不可为空',
        'province_id.number' => '省id格式错误',
        'city_id.number' => '市id格式错误',
        'area_id.number' => '区id格式错误',
        'video_width.require' => '视频宽度不可为空',
        'video_width.max' => '视频宽度不可超过10个字符',
        'video_height.require' => '视频高度不可为空',
        'video_height.max' => '视频高度不可超过10个字符',

        'photo_id.require' => '图片id不可为空',
    ];

    // 验证场景
    protected $scene = [
        // 新增视频接口验证规则
        'addVideo' => ['title', 'video', 'cover', 'province_id', 'city_id', 'area_id', 'video_width', 'video_height'],
        // 删除商家相册接口验证规则
        'deleteMerchantAlbum' => ['photo_id'],
    ];

    // 新增商家相册接口 验证场景定义
    public function sceneAddMerchantAlbum()
    {
        return $this->only(['image', 'name'])
            ->remove('name', 'require');
    }
}
