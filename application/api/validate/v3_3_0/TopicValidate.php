<?php

namespace app\api\validate\v3_3_0;

use think\Validate;

class TopicValidate extends Validate
{
    // 错误信息
    protected $message = [
        'topic_id' => '参数错误',
    ];

    /**
     * 保存话题浏览历史
     *
     * @return $this
     */
    public function sceneHistory()
    {
        return $this->append([
            'topic_id' => 'require|number|gt:0'
        ]);
    }
}
