<?php

namespace app\api\validate\v3_3_0;

use think\Validate;

class EssayValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'image_list' => ['require'],
        'title' => ['max' => 1500],
        'cover' => ['require'],
        'adcode' => ['number'],
        'location' => ['max' => 150],
        'cover_width' => ['require', 'max' => 10],
        'cover_height' => ['require', 'max' => 10],
        'relation_shop_id' => ['number'],
    ];

    // 错误信息
    protected $message = [
        'image_list.require' => '图片列表不可为空',
        'title.max' => '标题长度不可超过1500个字符',
        'cover.require' => '封面不可为空',
        'adcode.number' => '区域code格式错误',
        'location.max' => '地理位置不可超过150个字符',
        'cover_width.require' => '宽度不可为空',
        'cover_width.max' => '宽度不可超过10个字符',
        'cover_height.require' => '高度不可为空',
        'cover_height.max' => '高度不可超过10个字符',
        'relation_shop_id.number' => '关联店铺id格式错误',
    ];

    // 验证场景
    protected $scene = [
        // 新增随记接口验证规则
        'addEssay' => ['image_list', 'title', 'cover', 'adcode', 'location', 'cover_width', 'cover_height', 'relation_shop_id'],
    ];
}
