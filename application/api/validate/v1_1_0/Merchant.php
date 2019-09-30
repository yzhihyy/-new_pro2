<?php

namespace app\api\validate\v1_1_0;

use think\Validate;

class Merchant extends Validate
{
    // 验证规则
    protected $rule = [
        'image' => ['require', 'max' => 255],
        'thumb_image' => ['require', 'max' => 255],
        'name' => ['require', 'max' => 10],

        'content' => ['require', 'max' => 200],
        'image_list' => ['require'],
        'photo_id' => ['require'],

        'activity_id' => ['require'],

        'shop_address' => ['max' => 255],
        'shop_phone' => ['max' => 20],
        'operation_time' => ['max' => 100],
        'announcement' => ['max' => 255],
    ];

    // 错误信息
    protected $message = [
        'image.require' => '图片不可为空',
        'image.max' => '图片长度不可超过255个字符',
        'thumb_image.require' => '缩略图不可为空',
        'thumb_image.max' => '缩略图长度不可超过255个字符',
        'name.require' => '图片名称不可为空',
        'name.max' => '图片名称不可超过10个字符',

        'content.require' => '内容不可为空',
        'content.max' => '内容不可超过200个字符',
        'image_list.require' => '图片列表不可为空',

        'photo_id.require' => '图片id不可为空',
        'activity_id.require' => '活动id不可为空',

        'shop_address.max' => '地址不可超过255个字符',
        'shop_phone.max' => '电话不可超过20个字符',
        'operation_time.max' => '营业时间不可超过100个字符',
        'announcement.max' => '公告不可超过255个字符',
    ];

    // 验证场景
    protected $scene = [
        // 新增店铺推荐接口验证规则
        'addMerchantRecommend' => ['image', 'thumb_image', 'name'],
        // 新增商家活动接口验证规则
        'addMerchantActivity' => ['content', 'image_list'],
        // 删除商家相册接口验证规则
        'deleteMerchantAlbum' => ['photo_id'],
        // 删除店铺推荐接口验证规则
        'deleteMerchantRecommend' => ['photo_id'],
        // 删除商家活动接口验证规则
        'deleteMerchantActivity' => ['activity_id'],
        // 保存商家信息接口验证规则
        'saveMerchantInfo' => ['shop_address', 'shop_phone', 'operation_time', 'announcement'],
    ];

    // 新增商家相册接口 验证场景定义
    public function sceneAddMerchantAlbum()
    {
        return $this->only(['image','thumb_image', 'name'])
            ->remove('name', 'require');
    } 
}
