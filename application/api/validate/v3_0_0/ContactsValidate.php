<?php

namespace app\api\validate\v3_0_0;

use think\Validate;

class ContactsValidate extends Validate
{
    protected $message = [
        'contact_list.require' => '参数缺失'
    ];

    /**
     * 查询好友
     */
    public function sceneQueryFriendRelationship()
    {
        return $this->append([
            'contact_list' => ['require'],
        ]);
    }
}