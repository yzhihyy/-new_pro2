<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;

class UserModel extends AbstractModel
{
    protected $name = 'user';

    /**
     * 获取Query
     *
     * @param $where
     *
     * @return UserModel
     */
    public function getQuery($where)
    {
        $query = $this->alias('u')
            ->field([
                'u.id',
                'u.phone',
                'u.nickname',
                'u.avatar',
                'u.thumb_avatar',
                'u.login_time as loginTime',
                'u.account_status as accountStatus',
                'u.money',
                'u.platform',
                'u.token',
                'u.generate_time as generateTime',
                'u.wechat_nickname as wechatNickname',
                'u.wechat_unionid as wechatUnionid',
                'u.qq_nickname as qqNickname',
                'u.qq_unionid as qqUnionid',
                'u.bind_invite_code as bindInviteCode',
            ]);
        if (!empty($where) && is_array($where)) {
            foreach ($where as $field => $condition) {
                list($exp, $value) = $condition;
                switch ($field) {
                    case 'phone':
                        switch ($exp) {
                            case '=':
                                $query->where('u.phone', '=', $value);
                                break;
                            default:
                                break;
                        }
                        break;
                    case 'token':
                        switch ($exp) {
                            case '=':
                                $query->where('u.token', '=', $value);
                                break;
                            default:
                                break;
                        }
                        break;
                    default:
                        break;
                }
            }
        }
        return $query;
    }

    /**
     * 根据手机号获取用户
     *
     * @param $phone
     *
     * @return array|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserByPhone($phone)
    {
        $where = [
            'phone' => ['=', $phone]
        ];
        $query = $this->getQuery($where);
        return $query->find();
    }

    /**
     * 根据手机号获取商家
     *
     * @param $phone
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBusinessByPhone($phone)
    {
        $query = $this->alias('u')
            ->join('shop s', 'u.id = s.user_id', 'INNER')
            ->field([
                'u.id',
                'u.phone',
                's.account_status as accountStatus',
                's.online_status  as onlineStatus'
            ])
            ->where('u.phone', '=', $phone);
        return $query->find();
    }

    /**
     * 根据token获取用户
     *
     * @param $token
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserByToken($token)
    {
        $where = [
            'token' => ['=', $token]
        ];
        $query = $this->getQuery($where);
        return $query->find();
    }

    /**
     * 获取用户和关联的店铺信息
     *
     * @param $userId
     *
     * @return array|null|\PDOStatement|string|\think\Model
     *
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserAndShop($userId)
    {
        $query = $this->alias('u')
            ->field([
                'u.id as user_id',
                'u.phone as user_phone',
                'u.money',
                's.withdraw_rate',
                's.withdraw_holder_phone',
                's.withdraw_bankcard_num',
                's.withdraw_holder_name',
                's.withdraw_id_card',
                's.withdraw_bank_type',
                's.account_status as shop_account_status',
                's.online_status as shop_online_status',
                's.id as shop_id'
            ])
            ->join('shop s', 'u.id = s.user_id', 'inner')
            ->where(['u.id' => $userId]);

        return $query->find();
    }

    /**
     * 查找重复的设备ID数量
     *
     * @param array $where
     *
     * @return float|string
     */
    public function repeatDeviceIdCount($where = [])
    {
        return $this->where([
            ['id', '<>', $where['userId']],
            ['device_id', '=', $where['deviceId']]
        ])->count('id');
    }
}
