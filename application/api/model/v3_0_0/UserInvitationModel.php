<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class UserInvitationModel extends AbstractModel
{
    protected $name = 'user_invitation';

    /**
     * 统计用户邀请数量
     *
     * @param array $where
     *
     * @return float|string
     */
    public function getUserInvitedCount($where = [])
    {
        $query = $this->alias('ui')
            ->join('user u', 'u.id = invitee_user_id')
            ->where('ui.user_id', $where['userId']);

        if (!empty($where['valid'])) {
            $query->where('ui.repeat_device_id', 0);
        }

        return $query->count('ui.id');
    }

    /**
     * 获取用户邀请列表
     *
     * @param array $where
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInviteeList($where = [])
    {
        $query = $this->alias('ui')
            ->field([
                'u.phone',
                'u.generate_time as generateTime',
                'ui.install_time as installTime',
            ])
            ->join('user u', 'u.id = invitee_user_id')
            ->where('ui.user_id', $where['userId'])
            ->order('ui.generate_time', 'desc')
            ->limit($where['page'] * $where['limit'], $where['limit']);

        return $query->select()->toArray();
    }
}
