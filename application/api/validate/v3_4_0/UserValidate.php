<?php

namespace app\api\validate\v3_4_0;

use think\Validate;

class UserValidate extends Validate
{
    protected $message = [
        'name.require' => '姓名不可为空',
        'name.max' => '姓名不可超过:rule个字符',
        'phone.require' => '联系电话不可为空',
        'phone.mobile' => '联系电话格式有误',
        'cooperation_intention.require' => '合作意向不可为空',
    ];

    public function sceneApplyTheme()
    {
        return $this->append([
            'name' => 'require|max:50',
            'phone' => 'require|mobile',
            'cooperation_intention' => 'require'
        ]);
    }
}
