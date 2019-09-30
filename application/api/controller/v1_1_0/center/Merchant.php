<?php

namespace app\api\controller\v1_1_0\center;

use app\api\Presenter;

class Merchant extends Presenter
{
    /**
     * 新增商家相册
     * @return json
     */
    public function addMerchantAlbum()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/v1_1_0/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('addMerchantAlbum')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/v1_1_0/Shop');
            $where = ['user_id' => $userId];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在
                return apiError(config('response.msg10'));
            }
            // 实例化相册模型
            $PhotoAlbumModel = model('api/v1_1_0/PhotoAlbum');
            $photoAlbumData = [
                'shop_id' => $shopInfo['id'],
                'type' => 1,
                'image' => $paramsArray['image'],
                'thumb_image' => $paramsArray['thumb_image'],
                'generate_time' => date('Y-m-d H:i:s'),
            ];
            if (isset($paramsArray['name']) && $paramsArray['name'] != '') {
                $photoAlbumData['name'] = $paramsArray['name'];
            }
            $result = $PhotoAlbumModel->insertGetId($photoAlbumData);
            if (empty($result)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess(['photo_id' => $result]);
        } catch (\Exception $e) {
            $logContent = '新增商家相册接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 新增店铺推荐
     * @return json
     */
    public function addMerchantRecommend()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/v1_1_0/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('addMerchantRecommend')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/v1_1_0/Shop');
            $where = ['user_id' => $userId];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在
                return apiError(config('response.msg10'));
            }
            // 实例化相册模型
            $PhotoAlbumModel = model('api/v1_1_0/PhotoAlbum');
            $photoAlbumData = [
                'shop_id' => $shopInfo['id'],
                'type' => 2,
                'name' => $paramsArray['name'],
                'image' => $paramsArray['image'],
                'thumb_image' => $paramsArray['thumb_image'],
                'generate_time' => date('Y-m-d H:i:s'),
            ];
            $result = $PhotoAlbumModel->insertGetId($photoAlbumData);
            if (empty($result)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess(['photo_id' => $result]);
        } catch (\Exception $e) {
            $logContent = '新增店铺推荐接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 新增商家活动
     * @return json
     */
    public function addMerchantActivity()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/v1_1_0/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('addMerchantActivity')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 图片数组
            $imageArray = json_decode($paramsArray['image_list'], true);
            if (empty($imageArray)) {
                return apiError(config('response.msg38'));
            }
            // 上传图片张数限制
            $uploadMaxCount = config('parameters.upload_max_count_level_9');
            // 图片不能超过%d张
            if (count($imageArray) > $uploadMaxCount) {
                $msg = config('response.msg40');
                return apiError(sprintf($msg, $uploadMaxCount));
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/v1_1_0/Shop');
            $where = ['user_id' => $userId];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在
                return apiError(config('response.msg10'));
            }
            // 店铺id
            $shopId = $shopInfo->id;
            // 日期
            $nowDate = date('Y-m-d H:i:s');
            // 实例化店铺活动模型
            $model = $shopActivityModel = model('api/v1_1_0/ShopActivity');
            $shopActivityData = [
                'shop_id' => $shopId,
                'content' => $paramsArray['content'],
                'generate_time' => $nowDate
            ];

            // 启动事务
            $model->startTrans();
            $shopActivityResult = $shopActivityModel->insertGetId($shopActivityData);
            if (empty($shopActivityResult)) {
                return apiError(config('response.msg11'));
            }

            // 实例化相册模型
            $PhotoAlbumModel = model('api/v1_1_0/PhotoAlbum');
            $photoAlbumData = [];
            foreach ($imageArray as $imageValue) {
                if (empty($imageValue['image']) || empty($imageValue['thumb_image'])) {
                    return apiError(config('response.msg39'));
                }
                $tempArray = [
                    'shop_id' => $shopId,
                    'activity_id' => $shopActivityResult,
                    'type' => 3,
                    'image' => $imageValue['image'],
                    'thumb_image' => $imageValue['thumb_image'],
                    'generate_time' => $nowDate
                ];
                $photoAlbumData[] = $tempArray;
            }
            $photoAlbumResult = $PhotoAlbumModel->insertAll($photoAlbumData);
            if (empty($photoAlbumResult)) {
                return apiError(config('response.msg11'));
            }
            // 提交事务
            $model->commit();
            return apiSuccess(['activity_id' => $shopActivityResult]);
        } catch (\Exception $e) {
            // 回滚事务
            isset($model) && $model->rollback();
            $logContent = '新增商家活动接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 删除相册方法
     * @param int $fromType 来源接口
     * @return json
     */
    public function deleteAlbum($fromType)
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        switch ($fromType) {
            case 1;
                $apiName = 'deleteMerchantAlbum';
                $apiText = '删除商家相册接口';
                break;
            case 2;
                $apiName = 'deleteMerchantRecommend';
                $apiText = '删除店铺推荐接口';
                break;
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/v1_1_0/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene($apiName)->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/v1_1_0/Shop');
            $where = ['user_id' => $userId];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在
                return apiError(config('response.msg10'));
            }
            // 实例化相册模型
            $photoAlbumModel = model('api/v1_1_0/PhotoAlbum');
            $photoAlbumData = [
                'status' => 0
            ];
            $photoAlbumWhere = [
                ['shop_id', '=', $shopInfo->id],
                ['status', '=', 1],
                ['id', 'in', $paramsArray['photo_id']]
            ];
            $result = $photoAlbumModel->where($photoAlbumWhere)->update($photoAlbumData);
            if (empty($result)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = $apiText.'异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 删除商家相册接口
     * @return json
     */
    public function deleteMerchantAlbum()
    {
        return $this->deleteAlbum(1);
    }

    /**
     * 删除店铺推荐接口
     * @return json
     */
    public function deleteMerchantRecommend()
    {
        return $this->deleteAlbum(2);
    }

    /**
     * 删除商家活动接口
     * @return json
     */
    public function deleteMerchantActivity()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/v1_1_0/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('deleteMerchantActivity')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/v1_1_0/Shop');
            $where = ['user_id' => $userId];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在
                return apiError(config('response.msg10'));
            }
            // 实例化店铺活动模型
            $shopActivityModel = model('api/v1_1_0/ShopActivity');
            $shopActivityData = $photoAlbumData = ['status' => 0];
            $shopActivityWhere = [
                ['shop_id', '=', $shopInfo->id],
                ['status', '=', 1],
                ['id', 'in', $paramsArray['activity_id']]
            ];
            // 删除活动
            $shopActivityResult = $shopActivityModel->where($shopActivityWhere)->update($shopActivityData);
            if (empty($shopActivityResult)) {
                return apiError(config('response.msg11'));
            }

            // 实例化相册模型
            $photoAlbumModel = model('api/v1_1_0/PhotoAlbum');
            $photoAlbumWhere = [
                ['shop_id', '=', $shopInfo->id],
                ['status', '=', 1],
                ['type', '=', 3],
                ['activity_id', 'in', $paramsArray['activity_id']]
            ];
            // 删除活动图片
            $photoAlbumModel->where($photoAlbumWhere)->update($photoAlbumData);
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '删除商家活动接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 保存商家信息接口
     * @return json
     */
    public function saveMerchantInfo()
    {
        if (!$this->request->isPost()) {
            return apiError(config('response.msg4'));
        }
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/v1_1_0/Merchant');
            // 验证请求参数
            $checkResult = $validate->scene('saveMerchantInfo')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 请求参数和对应的数据库字段
            $field = [
                'shop_address' => 'shop_address',
                'shop_phone' => 'phone',
                'operation_time' => 'operation_time',
                'announcement' => 'announcement',
            ];
            foreach ($field as $key => $value) {
                if (isset($paramsArray[$key]) && ($paramsArray[$key] != '' || $key == 'announcement')) {
                    $shopData[$value] = $paramsArray[$key];
                }
            }
            if (empty($shopData)) {
                return apiSuccess();
            }
            // 用户id
            $userId = $this->getUserId();
            // 实例化店铺模型
            $shopModel = model('api/v1_1_0/Shop');
            $where = ['user_id' => $userId];
            $shopInfo = $shopModel->where($where)->find();
            if (empty($shopInfo)) {
                // 商家不存在
                return apiError(config('response.msg10'));
            }
            $result = $shopModel->where($where)->update($shopData);
            if (!($result || $result == 0)) {
                return apiError(config('response.msg11'));
            }
            return apiSuccess();
        } catch (\Exception $e) {
            $logContent = '保存商家信息接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}
