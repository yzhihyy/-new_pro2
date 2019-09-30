<?php

namespace app\api\model\v3_4_0;

use app\common\model\AbstractModel;

class ThemeArticleCommentModel extends AbstractModel
{
    protected $name = 'theme_article_comment';

    /**
     * 获取主题文章一级评论列表
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTopLevelCommentList($where = [])
    {
        $query = $this->alias('tac')
            ->field([
                'tac.article_id as articleId', // 文章ID
                'tac.id as commentId', // 评论ID
                'tac.like_count as likeCount', // 评论点赞数量
                'tac.content as content', // 评论内容
                'tac.generate_time as generateTime', // 评论日期
                'u.id as userId', // 用户ID
                's.id as shopId', // 店铺ID
                'CASE WHEN s.id > 0 THEN s.shop_name ELSE u.nickname END as nickname',
                'CASE WHEN s.id > 0 THEN s.shop_thumb_image ELSE u.thumb_avatar END as avatar',
            ])
            ->join('user u', 'u.id = tac.from_user_id')
            ->leftJoin('shop s', 's.id = tac.from_shop_id')
            ->where([
                'tac.article_id' => $where['articleId'],
                'tac.top_comment_id' => 0,
                'tac.status' => 1
            ]);

        // 该评论是否已经点赞
        if (isset($where['userId']) && $where['userId']) {
            $query->leftJoin('theme_article_action taa', "taa.action_type = 1 AND taa.comment_id = tac.id AND taa.user_id = {$where['userId']} AND taa.status = 1")
                ->field('IF(taa.id, 1, 0) as isLike');
        } else {
            $query->field('0 as isLike');
        }

        $query->order('tac.like_count', 'desc')
            ->order('tac.generate_time', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }
}
