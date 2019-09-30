<?php

namespace app\api\logic\v3_0_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_0_0\ShopModel;

class UserLogic extends BaseLogic
{
    /**
     * 获取店铺信息
     *
     * @param array|null $shop
     *
     * @return array
     */
    public function getShopInfo($shop)
    {
        $fields = [
            'shop_id',
            'shop_address',
            'merchant_name',
            'remark',
            'online_status',
        ];
        if (empty($shop)) {
            $responseData = array_fill_keys($fields, '');
            $responseData['shop_id'] = 0;
            $responseData['online_status'] = -1;
        } else {
            $responseData = array_combine($fields, [
                $shop['id'],
                $shop['shop_address'],
                $shop['real_name'],
                $shop['remark'],
                $shop['online_status'],
            ]);
        }

        $responseData['customer_service_phone'] = config('parameters.customer_service_phone');

        return $responseData;
    }

    /**
     * 保存店铺
     *
     * @param int        $userId
     * @param array      $paramsArray
     * @param array|null $shop
     * @param ShopModel  $shopModel
     */
    public function saveShop(int $userId, array $paramsArray, $shop, ShopModel $shopModel)
    {
        $shopData = [
            'user_id' => $userId,
            'shop_address' => $paramsArray['shop_address'],
            'real_name' => $paramsArray['merchant_name'],
            'online_status' => 2,
            'generate_time' => date('Y-m-d H:i:s')
        ];

        if (empty($shop)) {
            $shopData['shop_type'] = 1;
            $shopData['shop_image'] = config('parameters.merchant_shop_default_logo_img');
            $shopModel::create($shopData);
        } else {
            $shopModel::update($shopData, ['id' => $shop['id']]);
        }
    }
}