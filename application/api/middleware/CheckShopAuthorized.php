<?php

namespace app\api\middleware;

use app\api\model\v2_0_0\UserHasShopModel;
use app\api\traits\AbstractLogicTrait;

class CheckShopAuthorized
{
    use AbstractLogicTrait;

    /**
     * 校验是否有授权店铺中间件
     *
     * @param $request
     * @param \Closure $next
     *
     * @return mixed|\think\response\Json
     */
    public function handle($request, \Closure $next)
    {
        try {
            $user = $request->user;
            $shopPivotModel = model(UserHasShopModel::class);
            $shop = $shopPivotModel->getSelectedShop(['userId' => $user->id]);
            if (!$shop) {
                // 没有可管理的店铺
                list($code, $msg) = explode('|', config('response.msg55'));
                return apiError($msg, $code);
            }

            // 店铺已下线
            if ($shop['onlineStatus'] == 0) {
                list($code, $msg) = explode('|', config('response.msg64'));
                return apiError($msg, $code);
            }

            // 设置默认店铺
            $request->selected_shop = $shop;
            // 检测接口权限
            $detectResult = $this->detectInterfacePermissions($request->action(true), (string)$shop->authorizedRule);
            if (!empty($detectResult)) {
                return apiError($detectResult['msg'], $detectResult['code']);
            }

            return $next($request);
        } catch (\Exception $e) {
            $logContent = '异常日志：' . $e->getMessage() . '出错文件：' . $e->getFile() . '出错行号：' . $e->getLine();
            generateApiLog($logContent);
        }

        return apiError(config('response.msg5'));
    }
}
