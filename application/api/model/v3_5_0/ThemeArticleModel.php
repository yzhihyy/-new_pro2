<?php

namespace app\api\model\v3_5_0;

use app\common\model\AbstractModel;

class ThemeArticleModel extends AbstractModel
{
    protected $name = 'theme_article';

    /**
     * 获取商家文章列表
     *
     * @param array $where
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getShopArticleList($where)
    {
        $query = $this->alias('art')
            ->field([
                'art.id AS artId',
                'art.title',
                'art.cover',
                's.id AS shopId',
                's.shop_name AS shopName',
                's.shop_thumb_image AS shopThumbImage',
                'art.generate_time as generateTime',
                'tas.theme_id AS themeId'
            ])
            ->join('shop s', "s.id = art.shop_id AND s.account_status = 1 AND s.online_status = 1")
            ->leftJoin('theme_activity_shop tas', 'tas.article_id = art.id')
            ->leftJoin('theme_activity ta', 'tas.theme_id = ta.id')
            ->where([
                ['art.is_delete', '=', 0],
                ['art.shop_id', '=', $where['shopId']],
            ])
            ->whereRaw("ta.theme_type = 1 or ta.theme_type is null")
            ->order([
                'art.is_recommend' => 'desc',
                'art.id' => 'desc'
            ])
            ->group('art.id')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select();
    }
}
