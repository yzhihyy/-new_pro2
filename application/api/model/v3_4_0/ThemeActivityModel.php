<?php

namespace app\api\model\v3_4_0;

use app\common\model\AbstractModel;

class ThemeActivityModel extends AbstractModel
{
    protected $name = 'theme_activity';

    /**
     * 获取单个主题信息
     *
     * @param $id
     * @param array $where
     *
     * @return array|\PDOStatement|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTheme($id, $where = [])
    {
        $query = $this->field(true)
            ->where(array_merge([
                'id' => $id,
                'delete_status' => 0
            ], $where));

        return $query->find();
    }

    /**
     * 主题活动列表
     * @param $params
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function list(array $params)
    {
        $query = $this->alias('ta')
            ->field([
                'ta.id as theme_id',
                'ta.theme_title',
                'ta.theme_desc',
                'ta.theme_cover',
                'ta.theme_thumb_cover',
                'ta.theme_status',
                'ta.end_time',
                'ta.vote_type',
                'ta.vote_status',
                'ta.vote_limit',
                'ta.bonus_status',
                'ta.theme_type',
            ])
            ->leftJoin('user u', 'u.id = ta.user_id  AND u.is_robot = 0')
            ->leftJoin('shop s', 's.id = ta.shop_id')
            ->where([
                'ta.theme_status' => $params['theme_status'],
                'ta.delete_status' => 0,
                'ta.is_show' => 1,
            ])
            ->whereRaw('u.account_status is null or u.account_status = 1')
            ->whereRaw('s.online_status is null or s.online_status = 1');

        // 随机排序
        if (isset($params['order_rand']) && $params['order_rand']) {
            $query->orderRand();
        } else {
            $query->order('ta.theme_status', 'asc')
                ->order('ta.id', 'desc');
        }

        if(isset($params['city_id']) && !empty($params['city_id'])){
            $query->where('ta.city_id', '=', $params['city_id']);
        }
        //搜索用
        if(isset($params['keyword']) && !empty($params['keyword'])){
            $query->where('ta.theme_title|ta.theme_desc', 'like', '%'.$params['keyword'].'%');
        }

        //版本更新ios审核
        $rules = config('parameters.activity_display_rule_for_review');
        $requestVersion = request()->header('version');
        $requestPlatform = request()->header('platform');
        if ($rules['enable_flag'] && in_array($requestPlatform, $rules['platform']) && version_compare($requestVersion, $rules['version']) == 0) {
            $query->where('ta.id', 'not in', $rules['ids']);
        }

        if (isset($params['page']) && isset($params['limit'])) {
            $query->limit($params['page'] * $params['limit'], $params['limit']);
        }

        return $query->select()->toArray();
    }
}
