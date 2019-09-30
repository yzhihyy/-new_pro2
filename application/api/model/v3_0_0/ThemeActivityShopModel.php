<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class ThemeActivityShopModel extends AbstractModel
{
    protected $name = 'theme_activity_shop';

    /**
     * 主题活动详情店铺
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function activityDetailShop($where = [])
    {
        $query = $this->alias('tas')
            ->field([
                's.id AS shopId',
                's.shop_thumb_image AS shopThumbImage',
                's.shop_name AS shopName',
                's.pay_setting_type AS paySettingType',
                'tas.vote_count AS voteCount',
                'ta.id AS articleId',
                'ta.title AS articleTitle',
                'ta.cover AS articleCover'
            ])
            ->join('shop s', 's.id = tas.shop_id AND s.online_status = 1')
            ->join('theme_article ta', 'ta.id = tas.article_id AND ta.is_delete = 0')
            ->where([
                'tas.delete_status' => 0,
                'tas.theme_id' => $where['themeId'],
                'tas.status' => 2
            ])
            ->order('ta.sort', 'asc')
            ->order('tas.vote_count', 'desc')
            ->order('tas.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }

    /**
     * 主题店铺列表
     * @param array $params
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function themeShopList(array $params)
    {
        $query = $this->alias('tas')
            ->field([
                'tas.shop_id',
                'tas.theme_id',
                'tas.vote_count',
                's.shop_name',
                's.shop_image',
                's.shop_thumb_image',
                's.pay_setting_type',
                'ta.id AS article_id',
                'ta.title AS article_title',
                'ta.cover AS article_cover',
                'ta.thumb_cover as article_thumb_cover'

            ])
            ->join('shop s', 's.id = tas.shop_id AND s.online_status = 1')
            ->leftJoin('theme_article ta', 'ta.id = tas.article_id')
            ->where([
                'tas.delete_status' => 0,
                'tas.theme_id' => $params['theme_id'],
                'tas.status' => 2,
            ]);

        if(!empty($params['user_id'])){

            $query->field([
                'if(tvr.receive_status, tvr.receive_status, 0) as receive_status',
                "if(tvr.id, 1, 0) as priority"
            ]);
            if($params['vote_type'] == 2){
                $query->leftJoin('theme_vote_record tvr', "tvr.theme_id = {$params['theme_id']} AND tvr.user_id = {$params['user_id']} 
                     AND tvr.shop_id = tas.shop_id AND  DATE(tvr.generate_time) = "."'".$params['today']."'");
            }else{
                $query->leftJoin('theme_vote_record tvr', "tvr.theme_id = {$params['theme_id']} AND tvr.user_id = {$params['user_id']} 
                     AND tvr.shop_id = tas.shop_id");
            }
            $query->order('priority', 'desc')
            ->order('tvr.id', 'desc');

        }else{
            $query->field(['0 AS receive_status', '0 AS priority']);
        }

        $query->order('tas.vote_count', 'desc')
        ->order('tas.id', 'asc')
        ->limit($params['page'] * $params['limit'], $params['limit']);

        return $query->select()->toArray();
    }

    /**
     * 根据文章ID获取主题信息
     *
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
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
            ]);

        // 查询所有状态的主题
        if (isset($where['allThemeStatus']) && $where['allThemeStatus']) {
            $query->join('theme_activity ta', 'ta.id = tas.theme_id AND ta.delete_status = 0');
        } else { // 只查询进行中的主题
            $query->join('theme_activity ta', 'ta.id = tas.theme_id AND ta.theme_status = 2 AND ta.delete_status = 0');
        }

        $query->where(['tas.article_id' => $where['articleId'], 'tas.status' => 2, 'tas.delete_status' => 0])
        ->order('tas.generate_time', 'desc');

        return $query->find();
    }

    /**
     * 获取参与主题活动的店铺列表(不包含机器人)
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getThemeActivityShopList(array $where = [])
    {
        $themeIdArray = !is_array($where['themeId']) ? [$where['themeId']] : $where['themeId'];
        $query = $this->alias('tas')
            ->field([
                'tas.theme_id AS themeId',
                'tas.vote_count AS voteCount',
                'ta.theme_type AS themeType',
                'u.phone',
                'u.registration_id AS registratioinId',
            ])
            ->join('theme_activity ta', 'ta.id = tas.theme_id')
            ->join('shop s', 's.id = tas.shop_id AND s.online_status = 1')
            ->join('user u', 'u.id = tas.user_id AND u.account_status = 1 AND u.is_robot = 0')
            ->whereIn('tas.theme_id', $themeIdArray)
            ->where('tas.status', 2)
            ->where('tas.delete_status', 0)
            ->where('tas.shop_id', '>', 0)
            ->order('tas.theme_id', 'ASC')
            ->order('tas.vote_count', 'DESC')
            ->order('tas.generate_time', 'ASC')
            ->order('tas.id', 'ASC');

        return $query->select();
    }
}
