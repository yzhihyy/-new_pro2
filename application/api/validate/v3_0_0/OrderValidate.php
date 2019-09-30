<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class OrderValidate extends Validate
{
    // 错误信息
    protected $message = [
        'order_id.require' => '订单ID不可为空',
        'order_id.number' => '订单ID格式错误',
        'order_id.gt' => '订单ID格式错误'
    ];

    /**
     * 使用待核销的订单
     *
     * @return $this
     */
    public function sceneUseVerificationOrder()
    {
        return $this->append([
            'order_id' => 'require|number|gt:0'
        ]);
    }
}