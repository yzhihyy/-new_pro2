<?php

namespace app\api\model\v3_0_0;

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
                'ta.theme_type',
                'ta.theme_title',
                'ta.theme_desc',
                'ta.theme_cover',
                'ta.theme_thumb_cover',
                'ta.theme_status',
                'ta.end_time',
                'ta.vote_type',
                'ta.vote_limit'
            ])
            ->where('ta.theme_type', '=', 1)
            ->where('ta.theme_status', 'in', $params['theme_status'])
            ->where('ta.delete_status', '=', 0);

        // 随机排序
        if (isset($params['order_rand']) && $params['order_rand']) {
            $query->orderRand();
        } else {
            $query->order('ta.theme_status', 'asc')
                ->order('ta.id', 'desc');
        }

        if (isset($params['page']) && isset($params['limit'])) {
            $query->limit($params['page'] * $params['limit'], $params['limit']);
        }

        return $query->select()->toArray();
    }
}
