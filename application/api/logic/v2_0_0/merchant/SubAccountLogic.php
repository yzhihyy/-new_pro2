<?php

namespace app\api\logic\v2_0_0\merchant;

use app\api\logic\BaseLogic;
use app\api\model\v2_0_0\{
    UserHasShopModel, UserModel
};

class SubAccountLogic extends BaseLogic
{
    /**
     * 检测授权状态 店铺与用户的授权关系
     *
     * @param string $phone
     * @param int $shopId
     *
     * @return array
     *
     * @throws \Exception
     */
    public function detectUserAndShopAuzStatus(string $phone, int $shopId)
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $user = $userModel->getUserByPhone($phone);
        if (!empty($user)) {
            /** @var UserHasShopModel $userHasShopModel */
            $userHasShopModel = model(UserHasShopModel::class);
            // 获取授权店铺
            $authorizedShop = $userHasShopModel->getAuthorizedShop(['userId' => $user->id]);
            // 授权店铺ID数组
            $authorizedShopIdArray = array_column($authorizedShop, 'shopId');
            // 店铺已授权过此用户
            if (in_array($shopId, $authorizedShopIdArray)) {
                return $this->logicResponse(config('response.msg57'));
            }

            // 其他店铺已授权过此用户
            if (!empty($authorizedShop)) {
                return $this->logicResponse(config('response.msg58'));
            }
        }

        return $this->logicResponse([], $user);
    }
}
