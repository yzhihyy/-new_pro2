<?php
namespace app\api\model\v3_7_0;
use app\common\model\AbstractModel;

class UserCopperDetailModel extends AbstractModel
{
    protected $name = 'user_copper_detail';

   /**
     * 获取铜板明细记录
     *
     * @param array $where
     *
     * @return array
     */
    public function getCopperRecordList($where)
    {
        $query = $this->alias('cd')
            ->leftJoin('live_show ls', 'ls.id = cd.live_show_id')
            ->leftJoin('user u', 'u.id = ls.anchor_user_id')
            ->field([
                'ls.anchor_user_id',
                'ls.start_time',
                'ls.end_time',
                'u.nickname',
                'u.thumb_avatar',
                'cd.copper_type',
                'cd.copper_count',
                'cd.action_type',
                'cd.generate_time',
                'cd.payment_amount'
            ])
            ->where([
                'cd.user_id' => $where['userId']
            ])
            ->order('cd.generate_time', 'desc')
            ->order('cd.id', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);
        return $query->select()->toArray();
    }
}