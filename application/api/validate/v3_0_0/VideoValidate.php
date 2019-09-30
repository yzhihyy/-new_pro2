<?php

namespace app\api\validate\v3_0_0;

use app\api\model\v3_0_0\VideoReportTypeModel;
use think\Validate;

class VideoValidate extends Validate
{
    // 错误信息
    protected $message = [
        'followed_user_id.number' => '参数错误',
        'follow_type.require' => '参数缺失',
        'follow_type.in' => '参数错误',
        'video_id.require' => '参数缺失',
        'video_id.number' => '参数错误',
        'shop_reply_flag.in' => '参数错误',
        'content.require' => '请填写评论内容',
        'content.max' => '最多评论:rule个字符',
        'comment_id.number' => '参数错误',
        'hot_reply_id.require' => '参数缺失',
        'hot_reply_id.number' => '参数错误',
        'parent_comment_id.require' => '参数缺失',
        'parent_comment_id.number' => '参数错误',
        'type.require' => '参数缺失',
        'type.in' => '参数错误',
        'city_id' => '参数错误',
        'page' => '参数错误',
        'oldpage' => '参数错误',
        'user_id.require' => '参数缺失',
        'user_id.number' => '参数错误',
        'report_type_id.require' => '参数缺失',
        'report_type_id.number' => '参数错误',

    ];

    /**
     * 关注/取消关注
     *
     * @return VideoValidate
     */
    public function sceneFollow()
    {
        return $this->append([
            'followed_id' => 'require|number',
            'follow_type' => 'require|in:1,2'
        ]);
    }

    /**
     * 点赞/取消点赞(视频/评论)
     *
     * @return VideoValidate
     */
    public function sceneLike()
    {
        return $this->append([
            'video_id' => 'number',
            'comment_id' => 'number'
        ]);
    }

    /**
     * 视频评论/回复
     *
     * @return VideoValidate
     */
    public function sceneComment()
    {
        return $this->append([
            'video_id' => 'require|number',
            'shop_reply_flag' => 'in:0,1',
            'content' => 'require|max:100',
            'parent_comment_id' => 'number',
        ]);
    }

    /**
     * 视频评论列表
     *
     * @return VideoValidate
     */
    public function sceneCommentList()
    {
        return $this->append([
            'video_id' => 'require|number'
        ]);
    }

    /**
     * 视频评论回复列表
     *
     * @return VideoValidate
     */
    public function sceneCommentReplyList()
    {
        return $this->append([
            'comment_id' => 'require|number',
            'hot_reply_id' => 'require|number'
        ]);
    }

    /**
     * 视频转发
     *
     * @return VideoValidate
     */
    public function sceneShare()
    {
        return $this->append([
            'video_id' => 'require|number'
        ]);
    }

    /**
     * 首页视频列表
     *
     * @return $this
     */
    public function sceneHome()
    {
        return $this->append([
            'type' => ['require', 'in' => '1,2'],
            'city_id' => ['requireIf' => 'type,2', 'number', 'gt' => 0],
            'page' => ['number'],
            'oldpage' => ['number']
        ]);
    }

    /**
     * 保存视频播放历史
     *
     * @return $this
     */
    public function sceneHistory()
    {
        return $this->append(['video_list' => 'require']);
    }

    /**
     * 视频详情
     *
     * @return $this
     */
    public function sceneVideoDetail()
    {
        return $this->append([
            'video_id' => 'require|number',
        ]);
    }

    /**
     * 删除视频
     *
     * @return $this
     */
    public function sceneDeleteVideo()
    {
        return $this->append([
            'video_id' => 'require',
        ]);
    }


    /**
     * 举报视频
     * @return VideoValidate
     */
    public function sceneVideoReport()
    {
        return $this->append([
            'video_id' => 'require|number',
            'report_type_id' => 'require|number',
        ]);
    }

}
