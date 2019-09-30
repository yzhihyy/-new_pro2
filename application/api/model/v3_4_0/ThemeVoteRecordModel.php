<?php

namespace app\api\model\v3_4_0;

use app\common\model\AbstractModel;

class ThemeVoteRecordModel extends AbstractModel
{
    protected $name = 'theme_vote_record';

    /**
     * 获取已投票数量
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getVotedCount($where = [])
    {
        $query = $this->field([
            'shop_id AS shopId',
            'article_id AS articleId',
            'COUNT(1) AS votedCount'
        ])
            ->where([
                'theme_id' => $where['themeId'],
                'user_id' => $where['userId'],
            ]);

        if (isset($where['shopId']) && $where['shopId']) {
            $query->whereIn('shop_id', $where['shopId'])->group('shop_id');
        }

        if (isset($where['articleId']) && $where['articleId']) {
            $query->whereIn('article_id', $where['articleId'])->group('article_id');
        }

        if (isset($where['today']) && $where['today']) {
            $query->where('DATE(generate_time) = CURDATE()');
        }

        return $query->select()->toArray();
    }

    /**
     * 获取用户在主题下已领取的红包数量
     *
     * @param array $where
     *
     * @return float|string
     */
    public function getReceivedBonusCount($where = [])
    {
        return $this->where([
            'theme_id' => $where['themeId'],
            'user_id' => $where['userId'],
            'receive_status' => 1
        ])->count(1);
    }
}
