<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class FollowValidate extends Validate
{
    // 错误信息
    protected $message = [
        'followed_id.require' => '参数缺失',
        'followed_id.number' => '参数错误',
        'follow_to.require' => '参数缺失',
        'follow_to.in' => '参数错误',
        'follow_type.require' => '参数缺失',
        'follow_type.in' => '参数错误'
    ];

    /**
     * 关注/取消关注
     *
     * @return FollowValidate
     */
    public function sceneFollowAction()
    {
        return $this->append([
            'followed_id' => 'require|number',
            'follow_to' => 'require|in:1,2',
            'follow_type' => 'require|in:1,2'
        ]);
    }
}
