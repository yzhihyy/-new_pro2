<?php

namespace app\api\model\v3_4_0;

use app\common\model\AbstractModel;

class ThemeArticleModel extends AbstractModel
{
    protected $name = 'theme_article';


    /**
     * 获取主题文章列表
     * @param $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getThemeArticleList($where)
    {
        $subQuery = $this->name('theme_activity')
            ->alias('ta')
            ->leftJoin('theme_activity_shop tashop', 'ta.id = tashop.theme_id AND tashop.status = 2 AND tashop.delete_status = 0')
            ->field('tashop.theme_id, tashop.article_id, ta.theme_type')
            ->where('ta.theme_status = 2 AND ta.delete_status = 0');
        if(isset($where['theme_type'])){
            $subQuery = $subQuery->where('ta.theme_type', $where['theme_type']);
        }
        $subQuery = $subQuery->order('tashop.id', 'desc')->buildSql();

        $subQuery2 = $this->table($subQuery.' a')->group('a.article_id')->buildSql();
        $sql = '';
        if (!empty($where['isRecommend'])) {
            $sql .= " AND art.is_recommend = 1";
        }
        if (!empty($where['shopId'])) {
            $sql .= " AND art.shop_id = {$where['shopId']}";
        }
        //城市筛选
        if(isset($where['city_name'])){
            $sql .= " AND s.shop_city like '%{$where['city_name']}%'";
        }
        // 是否需要筛选店铺必须要有排序
        $joinCondition = 'AND s.recommend_sort > 0';
        if (!empty($where['formShopDetail'])) {
            $joinCondition = '';
        }
        $query = $this->alias('art')
            ->field([
                'art.id AS artId',
                'art.title',
                'art.cover',
                's.id AS shopId',
                's.shop_name AS shopName',
                's.shop_thumb_image AS shopThumbImage',
                'art.generate_time as generateTime',
                'tas.theme_id AS themeId',
                'tas.theme_type AS themeType',
            ])
            ->join('shop s', "s.id = art.shop_id AND s.account_status = 1 AND s.online_status = 1 {$joinCondition}")
            ->leftJoin($subQuery2.' tas', 'tas.article_id = art.id')
            ->where('art.is_delete = 0'.$sql)
            ->where('art.is_show', 1);

        // 模糊查找
        if(isset($where['keyword']) && !empty($where['keyword'])){
            $query->where('art.title', 'like', '%'.$where['keyword'].'%');
        }

        //版本更新ios审核
        $rules = config('parameters.article_display_rule_for_review');
        $requestVersion = request()->header('version');
        $requestPlatform = request()->header('platform');
        if ($rules['enable_flag'] && in_array($requestPlatform, $rules['platform']) && version_compare($requestVersion, $rules['version']) == 0) {
            $query->where('art.id', 'in', $rules['ids']);
        }

        $query->order('s.recommend_sort', 'desc')
            ->order('art.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}
