<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class UserValidate extends Validate
{
    // 错误信息
    protected $message = [
        'report_type_id.require' => '举报类型不可为空',
        'report_type_id.number' => '举报类型错误',
        'type.require' => '举报类型不可为空',
        'type.in' => '举报类型错误',
        'user_id.requireIf' => '举报用户不可为空',
        'user_id.number' => '举报用户不存在',
        'shop_id.requireIf' => '举报商家不可为空',
        'shop_id.number' => '举报商家不存在',

        'shop_address.require' => '地址不可为空',
        'shop_address.max' => '地址不可超过:rule个字符',
        'merchant_name.require' => '姓名不可为空',
        'merchant_name.max' => '姓名不可超过:rule个字符',

        'page.number' => '参数错误',
        'status.in' => '参数错误',
    ];

    /**
     * 举报视频
     * @return $this
     */
    public function sceneUserReport()
    {
        return $this->append([
            'report_type_id' => 'require|number',
            'type' => 'require|in:1,2',
            'user_id' => 'requireIf:type,1|number',
            'shop_id' => 'requireIf:type,2|number',
        ]);
    }

    public function sceneHandleBlackList()
    {
        return $this->append([
            'type' => 'require|in:1,2',
            'user_id' => 'requireIf:type,1|number',
            'shop_id' => 'requireIf:type,2|number',
        ]);
    }

    /**
     * 申请合作
     *
     * @return $this
     */
    public function sceneApplyCooperation()
    {
        return $this->append([
            'shop_address' => 'require|max:35',
            'merchant_name' => 'require|max:4',
        ]);
    }

    /**
     * 我的订单列表
     *
     * @return $this
     */
    public function sceneMyOrderList()
    {
        return $this->append([
            'page' => 'number',
            'status' => 'in:0,1,2,3'
        ]);
    }
}
