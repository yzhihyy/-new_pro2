<?php

namespace app\api\model\v3_4_0;

use app\common\model\AbstractModel;

class ThemeActivityShopModel extends AbstractModel
{
    protected $name = 'theme_activity_shop';

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
                's.phone',
                's.shop_address',
                's.longitude',
                's.latitude',
                's.setting',
                's.qq AS related_qq',
                's.wechat AS related_wechat',
                'ta.id AS article_id',
                'ta.title AS article_title',
                'ta.cover AS article_cover',
                'ta.thumb_cover as article_thumb_cover',
                's.pay_setting_type as related_pay_setting_type',

            ])
            ->join('shop s', 's.id = tas.shop_id AND s.online_status = 1')
            ->join('theme_article ta', 'ta.id = tas.article_id AND ta.is_delete = 0')
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
            
            if($params['theme_type'] == 1){//平台主题
                if($params['vote_type'] == 2){
                    $query->leftJoin('theme_vote_record tvr', "tvr.theme_id = {$params['theme_id']} AND tvr.user_id = {$params['user_id']} 
                     AND tvr.shop_id = tas.shop_id AND  DATE(tvr.generate_time) = "."'".$params['today']."'");
                }else{
                    $query->leftJoin('theme_vote_record tvr', "tvr.theme_id = {$params['theme_id']} AND tvr.user_id = {$params['user_id']} 
                     AND tvr.shop_id = tas.shop_id");
                }

            }else{//个人主题
                if($params['vote_type'] == 2){
                    $query->leftJoin('theme_vote_record tvr', "tvr.theme_id = {$params['theme_id']} AND tvr.user_id = {$params['user_id']} 
                     AND tvr.shop_id = tas.shop_id AND tvr.article_id = ta.id  AND  DATE(tvr.generate_time) = "."'".$params['today']."'");
                }else{
                    $query->leftJoin('theme_vote_record tvr', "tvr.theme_id = {$params['theme_id']} AND tvr.user_id = {$params['user_id']} 
                     AND tvr.shop_id = tas.shop_id  AND tvr.article_id = ta.id");
                }
            }

            $query->order('priority', 'desc')
                ->order('ta.sort', 'asc')
                ->order('tvr.id', 'desc');

        }else{
            $query->field(['0 AS receive_status', '0 AS priority']);
        }

        $query->order('tas.vote_count', 'desc')
        ->order('tas.id', 'desc')
        ->limit($params['page'] * $params['limit'], $params['limit']);

        return $query->select()->toArray();
    }
}
