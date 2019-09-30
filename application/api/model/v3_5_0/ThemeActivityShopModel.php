<?php

namespace app\api\model\v3_5_0;

use app\common\model\AbstractModel;

class ThemeActivityShopModel extends AbstractModel
{
    protected $name = 'theme_activity_shop';

    /**
     * 根据文章ID获取主题信息
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \Exception
     */
    public function getThemeByArticle($where = [])
    {
        $query = $this->alias('tas')
            ->field([
                'ta.id',
                'ta.theme_type',
                'ta.theme_title',
                'ta.theme_status',
                'ta.vote_status',
                'ta.vote_type',
            ])
            ->join('theme_activity ta', 'ta.id = tas.theme_id AND ta.theme_status = 2 AND ta.delete_status = 0')
            ->where(['tas.article_id' => $where['articleId'], 'tas.status' => 2, 'tas.delete_status' => 0])
            ->order('tas.generate_time', 'desc');
        return $query->find();
    }

}
