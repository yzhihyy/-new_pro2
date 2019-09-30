<?php
namespace app\api\controller\v3_5_0\user;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\api\model\v3_5_0\{
    FollowRelationModel, ShopModel
};

class Publish extends Presenter
{
    /**
     * 选择店铺接口（用户关注的店铺）
     * @return json
     */
    public function selectShop()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化用户关系表模型
            $followRelationModel = model(FollowRelationModel::class);
            $user = $this->request->user;
            $condition = [
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3'),
                'userId' => $user->id
            ];
            if (!empty($paramsArray['keyword'])) {
                $condition['keyword'] = $paramsArray['keyword'];
            }
            // 获取店铺列表
            $shopArray = $followRelationModel->getFollowShopList($condition);
            $shopList = [];
            if (!empty($shopArray)) {
                foreach ($shopArray as $value) {
                    $info = [
                        'shop_id' => $value['shopId'],
                        'shop_name' => $value['shop_name'],
                        'shop_thumb_image' => getImgWithDomain($value['shop_thumb_image']),
                        'shop_address' => $value['shop_address'],
                    ];
                    $shopList[] = $info;
                }
            }
            $data = [
                'shop_list' => $shopList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '选择店铺接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 搜索店铺接口
     * @return json
     */
    public function searchShop()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化店铺表模型
            $shopModel = model(ShopModel::class);
            $condition = [
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3')
            ];
            if (!empty($paramsArray['keyword'])) {
                $condition['keyword'] = $paramsArray['keyword'];
            }
            // 获取店铺列表
            $shopArray = $shopModel->searchShopList($condition);
            $shopList = [];
            if (!empty($shopArray)) {
                foreach ($shopArray as $value) {
                    $info = [
                        'shop_id' => $value['shopId'],
                        'shop_name' => $value['shop_name'],
                        'shop_thumb_image' => getImgWithDomain($value['shop_thumb_image']),
                        'shop_address' => $value['shop_address'],
                    ];
                    $shopList[] = $info;
                }
            }
            $data = [
                'shop_list' => $shopList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '搜索店铺接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}
