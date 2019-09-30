<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class MessageValidate extends Validate
{
    // 错误信息
    protected $message = [
        'msg_type.require' => '参数缺失',
        'msg_type.in' => '参数错误'
    ];

    /**
     * 商家消息列表
     *
     * @return MessageValidate
     */
    public function sceneMerchantList()
    {
        return $this->append([
            'msg_type' => 'require|in:0,1,2,3'
        ]);
    }

    /**
     * 用户消息列表
     *
     * @return MessageValidate
     */
    public function sceneUserList()
    {
        return $this->append([
            'msg_type' => 'require|in:0,2,3'
        ]);
    }
}
