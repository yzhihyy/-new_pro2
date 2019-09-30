<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class UserDataStatisticsModel extends AbstractModel
{
    protected $name = 'user_data_statistics';

    /**
     * 统计用户数据
     *
     * @param array $where
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserDataStatistics(array $where = [])
    {
        $query = $this->alias('uds')
            ->field([
                'uds.id AS statisticsId',
                'uds.count',
                'uds.use_flag AS useFlag',
            ])
            ->where('uds.user_id', $where['userId']);

        if (isset($where['type']) && is_numeric($where['type'])) {
            $query->where('uds.type', $where['type']);
        }

        if (isset($where['specifiedDate'])) {
            $query->whereRaw('DATE_FORMAT(uds.generate_time, "%Y-%m-%d") = :specifiedDate', [
                'specifiedDate' => $where['specifiedDate']
            ]);
        }

        return $query->select()->toArray();
    }
}