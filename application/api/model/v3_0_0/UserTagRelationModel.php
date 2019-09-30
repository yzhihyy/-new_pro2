<?php

namespace app\api\model\v3_0_0;

use app\common\model\AbstractModel;

class UserTagRelationModel extends AbstractModel
{
    protected $name = 'user_tag_relation';

    /**
     * 获取用户喜好的标签
     *
     * @param array $where
     * @return array
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserLikeTag(array $where = [])
    {
        return $this->alias('utr')
            ->field([
                't.id AS tagId',
            ])
            ->join('tag t', 't.id = utr.tag_id AND t.tag_type = 1')
            ->where('utr.user_id', $where['userId'])
            ->select()
            ->toArray();
    }
}
