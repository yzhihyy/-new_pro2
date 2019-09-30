<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class ThemeArticleModel extends AbstractModel
{
    protected $name = 'theme_article';

    /**
     * 获取主题文章详情
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getArticleDetail($where = [])
    {
        $query = $this->alias('ta')
            ->field([
                'ta.id AS articleId',
                'ta.title AS articleTitle',
                'ta.views AS articleViews',
                'ta.cover AS articleCover',
                'ta.thumb_cover AS articleThumbCover',
                'ta.content AS articleContent',
                'ta.video_position AS videoPosition',
                'ta.video_id AS videoId',
                's.id AS shopId',
                's.shop_name AS shopName',
                's.announcement AS shopAnnouncement',
                's.shop_image AS shopImage',
                's.shop_thumb_image AS shopThumbImage',
                's.shop_address AS shopAddress',
                's.shop_address_poi AS shopAddressPoi',
                's.phone AS shopPhone',
                's.operation_time AS operationTime',
                's.views AS shopViews',
                '0 AS distance',
                's.longitude',
                's.latitude',
                's.pay_setting_type AS paySettingType',
                's.qq AS shopQq',
                's.wechat AS shopWechat',
                's.setting AS shopSetting',
                'sc.name AS shopCategoryName'
            ])
            ->join('shop s', 's.id = ta.shop_id AND s.online_status = 1')
            ->leftJoin('shop_category sc', 's.shop_category_id = sc.id')
            ->where(['ta.id' => $where['articleId']]);

        if ($where['latitude'] && $where['longitude']) {
            $query->field("CASE WHEN (s.latitude is null or s.longitude is null) THEN 0 
                ELSE (GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',s.latitude,' ',s.longitude,')')))/0.0000092592666666667)
                END as distance" // 距离
            );
        }

        return $query->find();
    }

   /**
     * 获取主题文章列表
     *
     * @param array $where
     *
     * @return array
     */
    public function getThemeArticleList($where)
    {
        $subQuery = $this->name('theme_activity')
            ->alias('ta')
            ->leftJoin('theme_activity_shop tashop', 'ta.id = tashop.theme_id AND tashop.status = 2 AND tashop.delete_status = 0')
            ->field('tashop.theme_id, tashop.article_id')
            ->where('ta.theme_status = 2 AND ta.delete_status = 0 AND ta.theme_type = 1')
            ->order('tashop.id', 'desc')
            ->buildSql();
        $subQuery2 = $this->table($subQuery.' a')->group('a.article_id')->buildSql();
        $sql = '';
        if (!empty($where['isRecommend'])) {
            $sql .= " AND art.is_recommend = 1";
        }
        if (!empty($where['shopId'])) {
            $sql .= " AND art.shop_id = {$where['shopId']}";
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
                'tas.theme_id AS themeId'
            ])
            ->join('shop s', "s.id = art.shop_id AND s.account_status = 1 AND s.online_status = 1 {$joinCondition}")
            ->leftJoin($subQuery2.' tas', 'tas.article_id = art.id')
            ->where('art.is_delete = 0'.$sql);

        // 模糊查找
        if(isset($where['keyword']) && !empty($where['keyword'])){
            $query->where('art.title', 'like', '%'.$where['keyword'].'%');
        }

        $query->order('s.recommend_sort', 'desc')
            ->order('art.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}
