<?php

namespace app\api\validate\v2_0_0;

use think\Validate;

class ShopValidate extends Validate
{
    // 验证规则
    protected $rule = [
        'shop_name' => ['require', 'max' => 16],
        'shop_phone' => ['require', 'mobile'],
        'shop_address' => ['require', 'max' => 35],
        'shop_address_poi' => ['require', 'max' => 255],
        'shop_province' => ['require', 'max' => 16],
        'shop_city' => ['require', 'max' => 16],
        'shop_area' => ['require', 'max' => 16],
        'shop_detail_address' => ['require', 'max' => 255],
        'merchant_name' => ['require', 'max' => 4],
        'identity_card_number' => ['require', 'regex' => '/^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$/i'],
        'inviter' => ['max' => 4],
        'identity_card_front_face' => ['max' => 255],
        'identity_card_back_face' => ['max' => 255],
        'business_license' => ['max' => 255],
        'shop_logo' => ['max' => 255],

        'free_order_frequency' => ['require', 'number', 'between' => '2,9'],

        'shop_id' => 'require|number|gt:0',
        'latitude' => 'require|float|between:-180,180',
        'longitude' => 'require|float|between:-180,180',
        'sort' => 'require|in:1,2,3',
        'shop_category_id' => 'require|number|gt:0',

        'content' => ['require', 'max' => 200],
        'image_list' => ['require'],

        'recommend_id' => ['require', 'number', 'gt' => 0],

        'operation_time' => ['max' => 100],
        'announcement' => ['max' => 255],
    ];

    // 错误信息
    protected $message = [
        'shop_name.require' => '店铺名称不可为空',
        'shop_name.max' => '店铺名称不可超过:rule个字符',
        'shop_phone.require' => '电话号码不可为空',
        'shop_phone.mobile' => '请填写正确的手机号码',
        'shop_address.require' => '店铺地址不可为空',
        'shop_address.max' => '店铺地址不可超过:rule个字符',
        'shop_address_poi.require' => '店铺周边不可为空',
        'shop_address_poi.max' => '店铺周边不可超过:rule个字符',
        'shop_province.require' => '店铺所在省不可为空',
        'shop_province.max' => '店铺所在省不可超过:rule个字符',
        'shop_city.require' => '店铺所在市不可为空',
        'shop_city.max' => '店铺所在市不可超过:rule个字符',
        'shop_area.require' => '店铺所在区不可为空',
        'shop_area.max' => '店铺所在区不可超过:rule个字符',
        'shop_detail_address.require' => '店铺详细地址不可为空',
        'shop_detail_address.max' => '店铺详细地址不可超过:rule个字符',
        'merchant_name.require' => '商家姓名不可为空',
        'merchant_name.max' => '商家姓名不可超过:rule个字符',
        'identity_card_number.require' => '身份证号码不可为空',
        'identity_card_number.regex' => '请填写正确的身份证号码',
        'inviter.max' => '邀请人不可超过:rule个字符',
        'identity_card_front_face.require' => '身份证正面图片不可为空',
        'identity_card_front_face.max' => '身份证正面图片不可超过:rule个字符',
        'identity_card_back_face.require' => '身份证背面图片不可为空',
        'identity_card_back_face.max' => '身份证背面图片不可超过:rule个字符',
        'business_license.require' => '营业执照不可为空',
        'business_license.max' => '营业执照不可超过:rule个字符',
        'shop_logo.require' => '门头照不可为空',
        'shop_logo.max' => '门头照不可超过:rule个字符',

        'free_order_frequency.require' => '免单次数不可为空',
        'free_order_frequency.number' => '免单次数格式错误',
        'free_order_frequency.between' => '免单次数介于:1-:2次',

        'shop_id.require' => '店铺ID不可为空',
        'shop_id.number' => '店铺ID格式错误',
        'shop_id.gt' => '店铺ID必须大于:rule',

        'latitude.require' => '经纬度不可为空',
        'latitude.float' => '经纬度格式错误',
        'latitude.between' => '经纬度范围错误',
        'longitude.require' => '经纬度不可为空',
        'longitude.float' => '经纬度格式错误',
        'longitude.between' => '经纬度范围错误',

        'shop_ids.require' => '请选择要关联的店铺',

        'content.require' => '内容不可为空',
        'content.max' => '内容不可超过:rule个字符',
        'image_list.require' => '图片列表不可为空',

        'recommend_id.require' => '店铺推荐ID不可为空',
        'recommend_id.number' => '店铺ID格式错误',
        'recommend_id.gt' => '店铺ID格式错误',

        'operation_time.max' => '营业时间不可超过:rule个字符',
        'announcement.max' => '公告不可超过:rule个字符',
    ];

    // 申请店铺|申请分店
    public function sceneApplyShop()
    {
        return $this->only([
            'shop_name',
            'shop_phone',
            //'shop_address',
            'shop_address_poi',
            'shop_province',
            'shop_city',
            'shop_area',
            'shop_detail_address',
            'latitude',
            'longitude',
            'merchant_name',
            'identity_card_number',
            'inviter',
            'identity_card_front_face',
            'identity_card_back_face',
            'business_license',
            'shop_logo',
        ]);
    }

    // 免单设置
    public function sceneFreeOrderSetting()
    {
        return $this->only(['free_order_frequency']);
    }

    // 店铺详情
    public function sceneGetShopDetail()
    {
        return $this->only(['shop_id', 'longitude', 'latitude']);
    }
    // 店铺详情
    public function sceneMerchantGetShopDetail()
    {
        return $this->only(['longitude', 'latitude']);
    }

    // 根据店铺分类获取店铺列表
    public function sceneGetShopListByCategoryId()
    {
        return $this->only(['shop_category_id', 'longitude', 'latitude', 'sort']);
    }

    // 添加关联店铺
    public function sceneAddAssociateShop()
    {
        return $this->only(['shop_ids']);
    }

    // 新增店铺推荐
    public function sceneAddRecommend()
    {
        return $this->only(['content', 'image_list']);
    }

    // 删除店铺推荐
    public function sceneDeleteRecommend()
    {
        return $this->only(['recommend_id']);
    }

    // 保存商家信息
    public function sceneSaveInfo()
    {
        return $this->only(['shop_address', 'operation_time', 'announcement'])->remove('shop_address', 'require');
    }

    /**
     * 获取推荐详情
     *
     * @return ShopValidate
     */
    public function sceneGetShopRecommendDetail()
    {
        return $this->only(['recommend_id', 'longitude', 'latitude']);
    }

    /**
     * 获取免单卡使用店铺
     *
     * @return ShopValidate
     */
    public function sceneGetFreeCardShopList()
    {
        return $this->only(['shop_id', 'longitude', 'latitude']);
    }
}
