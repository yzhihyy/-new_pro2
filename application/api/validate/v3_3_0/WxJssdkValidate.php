<?php

namespace app\api\validate\v3_3_0;

use think\Validate;

class WxJssdkValidate extends Validate
{
    // 错误信息
    protected $message = [
        'url.require' => '参数缺失',
        'url.url' => '参数错误',

    ];

    /**
     * 分享接口
     *
     * @return $this
     */
    public function sceneShare()
    {
        return $this->append([
            'url' => 'require|url',
        ]);
    }
}
