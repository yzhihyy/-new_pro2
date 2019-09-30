<?php

namespace app\api\model\v3_7_0;

use app\common\model\AbstractModel;

class UserCoverModel extends AbstractModel
{
    protected $name = 'user_cover';

    public function getUserCoverList($where)
    {
        $query = $this->alias('uc')
            ->field([
                'uc.id',
                'uc.cover',
                'uc.thumb_cover',
            ])
            ->where([
                ['uc.user_id', '=', $where['userId']]
            ]);
        return $query->select();
    }
}
