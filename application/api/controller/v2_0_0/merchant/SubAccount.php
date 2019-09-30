<?php

namespace app\api\controller\v2_0_0\merchant;

use app\api\Presenter;
use think\Response\Json;
use app\api\logic\v2_0_0\{
    merchant\SubAccountLogic, user\UserLogic
};
use app\api\model\v2_0_0\UserHasShopModel;
use app\api\validate\v2_0_0\SubAccountValidate;

class SubAccount extends Presenter
{
    /**
     * 子账号管理
     *
     * @return Json
     */
    public function subAccountManage()
    {
        try {
            /** @var UserHasShopModel $userHasShopModel */
            $userHasShopModel = model(UserHasShopModel::class);
            // 子账号
            $subAccounts = $userHasShopModel->getSubAccount([
                'shopId' => $this->request->selected_shop->id,
                'userId' => $this->request->user->id,
            ]);
            $responseData = [];
            foreach ($subAccounts as $account) {
                $responseData[] = [
                    'user_id' => $account['userId'],
                    'phone' => $account['phone'],
                    'remark' => $account['userRemark'] ?? $account['nickname'],
                    'avatar' => getImgWithDomain($account['avatar']),
                    'thumb_avatar' => getImgWithDomain($account['thumbAvatar']),
                ];
            }

            return apiSuccess(['list' => $responseData]);
        } catch (\Exception $e) {
            generateApiLog("子账号管理接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 检测子账号与店铺的授权状态
     *
     * @return Json
     */
    public function detectSubAccount()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, SubAccountValidate::class . '.Detect');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            $subAccountLogic = new SubAccountLogic();
            // 店铺ID
            $shopId = $this->request->selected_shop->id;
            // 检测用户与店铺的授权状态
            $result = $subAccountLogic->detectUserAndShopAuzStatus($paramsArray['phone'], $shopId);
            if (!empty($result['msg'])) {
                return apiError($result['msg']);
            }

            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("检测子账号接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 子账号验证码校验
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function subAccountCodeVerify()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, SubAccountValidate::class . '.CodeVerify');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            // 校验验证码
            $codeVerify = $this->phoneCodeVerify($paramsArray['phone'], $paramsArray['code']);
            if (!empty($codeVerify)) {
                return apiError($codeVerify);
            }

            $subAccountLogic = new SubAccountLogic();
            // 当前店铺
            $selectedShop = $this->request->selected_shop;
            // 检测用户与店铺的授权状态
            $result = $subAccountLogic->detectUserAndShopAuzStatus($paramsArray['phone'], $selectedShop->id);
            if (!empty($result['msg'])) {
                return apiError($result['msg']);
            }

            /** @var UserHasShopModel $userHasShopModel */
            $userHasShopModel = model(UserHasShopModel::class);
            // 开启事务
            $userHasShopModel->startTrans();

            // 用户信息
            $user = $result['data'];
            // 用户不存在,注册用户
            if (empty($user)) {
                $userLogic = new UserLogic();
                $user = $userLogic->createUserAndReturnInstance(['phone' => $paramsArray['phone']]);
            }

            // 将当前店铺的管理权限赋予此用户
            $userHasShopData = [
                'user_id' => $user->id,
                'shop_id' => $selectedShop->id,
                'shop_user_id' => $selectedShop->shopUserId,
                'rule' => config('parameters.merchant_sub_account_default_rule'),
                'generate_time' => date('Y-m-d H:i:s'),
            ];
            $userHasShopModel::create($userHasShopData);

            // 提交事务
            $userHasShopModel->commit();

            return apiSuccess();
        } catch (\Exception $e) {
            isset($userHasShopModel) && $userHasShopModel->rollback();
            generateApiLog("子账号验证码校验接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 设置子账号备注
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function setSubAccountRemark()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, SubAccountValidate::class . '.SetRemark');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            // 条件
            $condition = [
                'type' => 2,
                'shopId' => $this->request->selected_shop->id,
                'userId' => $paramsArray['user_id'],
            ];
            /** @var UserHasShopModel $userHasShopModel */
            $userHasShopModel = model(UserHasShopModel::class);
            // 子账号
            $subAccount = $userHasShopModel->getSubAccount($condition);
            if (empty($subAccount)) {
                return apiError(config('response.msg60'));
            }

            $userHasShopModel::update(['user_remark' => $paramsArray['remark']], [
                'user_id' => $condition['userId'],
                'shop_id' => $condition['shopId'],
            ]);

            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("设置子账号备注接口异常：{$e->getMessage()}");
        }

        return apiError();
    }

    /**
     * 删除子账号
     *
     * @return Json
     *
     * @throws \Exception
     */
    public function deleteSubAccount()
    {
        try {
            // 请求参数
            $paramsArray = $this->request->post();
            // 参数校验
            $validateResult = $this->validate($paramsArray, SubAccountValidate::class . '.Delete');
            if ($validateResult !== true) {
                return apiError($validateResult);
            }

            /** @var UserHasShopModel $userHasShopModel */
            $userHasShopModel = model(UserHasShopModel::class);
            $userHasShopModel::destroy([
                'user_id' => $paramsArray['user_id'],
                'shop_id' => $this->request->selected_shop->id,
            ]);

            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog("删除子账号接口异常：{$e->getMessage()}");
        }

        return apiError();
    }
}
