<?php

namespace app\api\validate\v3_4_0;

use think\Validate;

class ThemeValidate extends Validate
{
    // 错误信息
    protected $message = [
        'article_id.require' => '参数缺失',
        'article_id.number' => '文章ID错误',
        'comment_id.require' => '参数缺失',
        'comment_id.number' => '参数错误',
        'content.require' => '请填写评论内容',
        'content.max' => '最多评论:rule个字符',
        'article_id.require' => '参数缺失',
        'article_id.number' => '文章ID错误',
    ];

    /**
     * 主题文章评论列表
     *
     * @return $this
     */
    public function sceneArticleCommentList()
    {
        return $this->append([
            'article_id' => 'require|number'
        ]);
    }

    /**
     * 主题文章评论点赞
     *
     * @return $this
     */
    public function sceneArticleCommentLike()
    {
        return $this->append([
            'comment_id' => 'require|number'
        ]);
    }

    /**
     * 主题文章评论
     *
     * @return $this
     */
    public function sceneArticleComment()
    {
        return $this->append([
            'article_id' => 'require|number',
            'content' => 'require|max:100'
        ]);
    }

    /**
     * 投票
     *
     * @return $this
     */
    public function sceneVote()
    {
        return $this->append([
            'theme_id' => 'require|number',
            'shop_id' => 'require|number',
            'article_id' => 'require|number'
        ]);
    }
}
