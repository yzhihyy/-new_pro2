<?php

namespace app\api\model;

use app\common\model\AbstractModel;

class Captcha extends AbstractModel
{
    /**
     * 统计当天发送短信验证码的数量
     *
     * @param $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCountToday($where)
    {
        $query = $this->alias('c')
            ->field([
                "COUNT(c.id) as countNum"
            ]);
        $query->where('c.phone', '=', $where['phone'])
            ->whereTime('generate_time', 'today');
        if (isset($where['type'])) {
            $query->where('c.type', '=', $where['type']);
        }
        return $query->find();
    }

    /**
     * 校验登录验证码是否可用
     *
     * @param $where
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkLoginCode($where)
    {
        $date = date('Y-m-d H:i:s', time());
        $query = $this->alias('c')
            ->field([
                'c.id',
                'c.phone',
                'c.code',
                'c.type',
                'c.expire_time',
                'c.generate_time'
            ])
            ->where('c.phone', '=', $where['phone'])
            ->where('c.type', '=', 1)
            ->where('c.code', '=', $where['code'])
            ->where('c.expire_time', '> time', $date)
            ->order('c.generate_time', 'DESC');
        return $query->find();
    }

    /**
     * 获取最后一次发送时间
     *
     * @param $where
     * @return mixed
     */
    public function getLastSendTime($where)
    {
        $query = $this->alias('c')
            ->where('phone', '=', $where['phone'])
            ->where('type', '=', 1)
            ->order('c.generate_time', 'DESC')
            ->limit(0, 1);
        return $query->value('c.generate_time');
    }
}