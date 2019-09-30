<?php

namespace app\api\validate\v3_3_0;

use think\Validate;

class VideoValidate extends Validate
{
    // 错误信息
    protected $message = [
        'city_id' => '参数错误',

        'mode.require' => '参数缺失',
        'mode.in' => '参数错误',
        'type.requireIf' => '参数缺失',
        'type.in' => '参数错误',
        'topic_id' => '参数错误',
        'video_id' => '参数错误',
    ];

    /**
     * 首页 - 地区
     *
     * @return $this
     */
    public function sceneRegion()
    {
        return $this->append([
            'city_id' => 'require|number|gt:0',
        ]);
    }

    /**
     * 获取视频或随记列表
     *
     * @return $this
     */
    public function sceneVideoList()
    {
        return $this->append([
            'mode' => 'require|in:1,2,3',   // 1:获取指定话题下的视频和随记, 2:获取指定城市下的视频和随记, 3:获取指定随记详情
            'type' => 'requireIf:mode,1|requireIf:mode,2|in:0,1',   // 0:获取视频和随记, 1:获取视频
            'topic_id' => 'requireIf:mode,1|number',
            'video_id' => 'requireIf:mode,2|requireIf:mode,3|number',
            'city_id' => 'requireIf:mode,2'
        ]);
    }
}
