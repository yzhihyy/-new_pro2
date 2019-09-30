<?php

namespace app\api\model\v2_0_0;

use app\common\model\AbstractModel;
use think\exception\DbException;
use think\db\exception\{
    DataNotFoundException, ModelNotFoundException
};

class UserHasShopModel extends AbstractModel
{
    protected $name = 'user_has_shop';

    /**
     * 获取已授权的店铺
     *
     * @param array $where
     *
     * @return array
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public function getAuthorizedShop($where = [])
    {
        $condition = 's.id = uhs.shop_id';
        $type = $where['type'] ?? 1;
        switch ($type) {
            // 获取已授权的店铺(店铺必须已上线)
            case 1:
                $condition .= ' AND s.online_status = 1';
                break;
            // 获取已授权的店铺
            case 2:
                break;
            default:
                break;
        }

        $query = $this->alias('uhs')
            ->field([
                'uhs.id as shopPivotId',
                'uhs.selected_shop_flag as selectedShopFlag',
                's.id as shopId',
                's.shop_name as shopName',
                's.shop_image as shopImage',
                's.shop_thumb_image as shopThumbImage',
                's.shop_address as shopAddress',
                's.tally_time as tallyTime',
                's.online_status as onlineStatus',
            ])
            ->join('shop s', $condition)
            ->where('uhs.user_id', $where['userId'])
            ->order('uhs.selected_shop_flag', 'desc')
            ->order('uhs.generate_time', 'desc');

        if (isset($where['shopId'])) {
            $query->where('uhs.shop_id', $where['shopId']);
        }

        return $query->select()->toArray();
    }

    /**
     * 子账号管理
     *
     * @param array $where
     *
     * @return array
     *
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     */
    public function getSubAccount(array $where = [])
    {
        $query = $this->alias('uhs')
            ->field([
                'uhs.id AS shopPivotId',
                'uhs.user_remark AS userRemark',
                'u.id AS userId',
                'u.phone',
                'u.nickname',
                'u.avatar',
                'u.thumb_avatar AS thumbAvatar',
            ])
            ->where('uhs.shop_id', $where['shopId'])
            ->join('user u', 'u.id = uhs.user_id');
        $type = $where['type'] ?? 1;
        switch ($type) {
            // 子账号管理
            case 1:
                $result = $query->where('uhs.user_id', '<>', $where['userId'])
                    ->order('uhs.generate_time', 'DESC')
                    ->order('uhs.id', 'DESC')
                    ->select()
                    ->toArray();
                break;
            // 获取子账号信息
            case 2:
                $result = $query->where('uhs.user_id', $where['userId'])
                    ->find();
                break;
            default:
                $result = [];
                break;
        }

        return $result;
    }

    /**
     * 获取当前选中的店铺
     *
     * @param array $where
     *
     * @return array|null|\PDOStatement|string|\think\Model
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getSelectedShop($where = [])
    {
        return $this->alias('uhs')
            ->field([
                's.id',
                's.shop_category_id as shopCategoryId',
                's.user_id as shopUserId',
                's.shop_name as shopName',
                's.shop_image as shopImage',
                's.shop_thumb_image as shopThumbImage',
                's.shop_address as shopAddress',
                's.shop_address_poi as shopAddressPoi',
                's.phone as shopPhone',
                's.introduce as shopIntroduce',
                's.balance as shopBalance',
                's.receipt_qr_code as receiptQrCode',
                's.receipt_qr_code_poster as receiptQrCodePoster',
                's.longitude',
                's.latitude',
                's.account_status as accountStatus',
                's.online_status as onlineStatus',
                's.operation_time as operationTime',
                's.tally_time as tallyTime',
                's.free_order_frequency as freeOrderFrequency',
                's.real_name as realName',
                's.id_number as idNumber',
                's.identity_card_front_face_img as identityCardFrontFaceImg',
                's.identity_card_back_face_img as identityCardBackFaceImg',
                's.identity_card_holder_half_img as identityCardHolderHalfImg',
                's.withdraw_rate as withdrawRate',
                's.withdraw_holder_phone as withdrawHolderPhone',
                's.withdraw_bankcard_num as withdrawBankcardNum',
                's.withdraw_holder_name as withdrawHolderName',
                's.withdraw_id_card as withdrawIdCard',
                's.withdraw_bank_type as withdrawBankType',
                's.inviter',
                's.announcement',
                's.is_recommend as isRecommend',
                's.recommend_sort as recommendSort',
                's.recommend_image as recommendImage',
                's.generate_time as generateTime',
                's.shop_province as shopProvince',
                's.shop_city as shopCity',
                's.shop_area as shopArea',
                'uhs.id as shopPivotId',
                'uhs.shop_user_id AS authorizedShopUserId',
                'uhs.user_id as authorizedUserId',
                'uhs.shop_id AS authorizedShopId',
                'uhs.rule as authorizedRule',
                'uhs.collect_push_flag as collectPushFlag',
                'uhs.selected_shop_flag as selectedShopFlag',
                'u.money AS shopUserMoney',
            ])
            ->join('shop s', 's.id = uhs.shop_id')
            ->join('user u', 'u.id = s.user_id')
            ->where('uhs.user_id', $where['userId'])
            ->where('uhs.selected_shop_flag', 1)
            ->find();
    }

    /**
     * 获取切换到指定店铺的用户信息
     *
     * @param array $where
     *
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getSelectedShopUser($where = [])
    {
        $query = $this->alias('uhs')
            ->field([
                'u.registration_id as registrationId',
            ])
            ->join('user u', 'u.id = uhs.user_id')
            ->where('uhs.shop_id', $where['shopId'])
            ->where('uhs.selected_shop_flag', 1);

        if (isset($where['collectPushFlag'])) {
            $query->where('uhs.collect_push_flag', $where['collectPushFlag']);
        }

        return $query->select()->toArray();
    }
}
