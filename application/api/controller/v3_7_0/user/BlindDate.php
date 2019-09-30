<?php

namespace app\api\controller\v3_7_0\user;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\api\model\v3_0_0\{
    AreaModel
};
use app\api\model\v3_6_0\{
    UserModel
};
use app\api\model\v3_7_0\{
    AnchorUserModel, LiveShowModel, VideoModel
};
use app\api\validate\v3_7_0\{
    BlindDateValidate
};
use app\api\service\UploadService;

class BlindDate extends Presenter
{
    /**
     * 获取申请相亲的状态
     *
     * @return Json
     */
    public function getApplyBlindDateStatus()
    {
        try {
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 主播用户
            $anchorUserModel = model(AnchorUserModel::class);
            $anchorUser = $anchorUserModel->where(['user_id' => $userId])->find();
            // 未申请
            $status = 3;
            // 理由
            $reason = '';
            // 申请信息
            $applyInfo = [];
            if (!empty($anchorUser)) {
                $status = $anchorUser['status'];
                $reason = $anchorUser['check_result_reason'];
                $applyInfo['contact'] = $anchorUser['user_name'];
                $applyInfo['phone'] = $anchorUser['phone'];
                // 用户模型
                $userModel = model(UserModel::class);
                $user = $userModel->where(['id' => $userId])->find();
                $applyInfo['gender'] = $user['gender'];
                $applyInfo['adcode'] = $user['adcode'];
                $applyInfo['location'] = $user['location'];
                $applyInfo['longitude'] = $user['longitude'];
                $applyInfo['latitude'] = $user['latitude'];
                // 实例化视频模型
                $videoModel = model(VideoModel::class);
                $videoWhere = [
                    ['user_id', '=', $userId],
                    ['anchor_id', '>', 0],
                ];
                $video = $videoModel->where($videoWhere)->find();
                $applyInfo['video_id'] = $video['id'];
                $applyInfo['video_cover_url'] = $video['cover_url'];
            }
            $data = [
                'apply_status' => $status,
                'check_result_reason' => $reason,
                'apply_info' => $applyInfo ? $applyInfo : (object)[]
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('获取申请相亲的状态接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 获取我发布的视频列表
     *
     * @return Json
     */
    public function getMyPublishVideoList()
    {
        try {
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 请求参数
            $paramsArray = input();
            // 视频模型
            $videoModel = model(VideoModel::class);
            $condition = [
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_3'),
                'userId' => $userId
            ];
            // 获取我发布的视频列表
            $videoArray = $videoModel->getMyVideoList($condition);
            $videoList = [];
            if (!empty($videoArray)) {
                foreach ($videoArray as $value) {
                    $info = [
                        'video_id' => $value['videoId'],
                        'cover_url' => getImgWithDomain($value['cover_url']),
                        'video_url' => $value['video_url'],
                    ];
                    $videoList[] = $info;
                }
            }
            $data = [
                'video_list' => $videoList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('获取我发布的视频列表接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 申请相亲接口
     *
     * @return Json
     */
    public function applyBlindDate()
    {
        try {
            // 请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(BlindDateValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('applyBlindDate')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 校验验证码
            $codeVerify = $this->phoneCodeVerify($paramsArray['phone'], $paramsArray['code']);
            if (!empty($codeVerify)) {
                return apiError($codeVerify);
            }
            $user = $this->request->user;
            $userId = $user->id;
            // 主播用户
            $model = $anchorUserModel = model(AnchorUserModel::class);
            $anchorUser = $anchorUserModel->where(['user_id' => $userId])->find();
            if (!empty($anchorUser) && in_array($anchorUser['status'], [0, 1])) {
                return apiError(config('response.msg109'));
            }
            $date = date('Y-m-d H:i:s');
            // 主播设置
            $settings = $this->getSettingsByGroup('anchor');
            $meetingPrice = $discountPer = 0;
            if (!empty($settings)) {
                $meetingPrice = $settings['meeting_price']['value'];
                $discountPer = $settings['discount_per']['value'];
            }
            // 申请数据
            $anchorUserData = [
                'user_id' => $userId,
                'user_name' => $paramsArray['contact'],
                'phone' => $paramsArray['phone'],
                'meeting_price' => $meetingPrice,
                'discount_per' => $discountPer,
                'generate_time' => $date,
                'status' => 0
            ];

            // 启动事务
            $model->startTrans();
            // 实例化视频模型
            $videoModel = model(VideoModel::class);
            $videoWhere = [
                'id' => $paramsArray['video_id'],
                'user_id' => $userId
            ];
            // 视频被驳回过
            if (isset($anchorUser['status']) && $anchorUser['status'] == 2) {
                $anchorUserResult = $anchorUserModel->where(['id' => $anchorUser['id']])->update($anchorUserData);
                if (empty($anchorUserResult)) {
                    $model->rollback();
                    return apiError(config('response.msg11'));
                }
                $videoFindWhere = [
                    ['user_id', '=', $userId],
                    ['anchor_id', '>', 0],
                ];
                $video = $videoModel->where($videoFindWhere)->find();
                if ($video['id'] != $paramsArray['video_id']) {
                    $videoFindResult = $videoModel->where($videoFindWhere)->update(['anchor_id' => 0]);
                    // 更新数据
                    $videoResult = $videoModel->where($videoWhere)->update(['anchor_id' => $anchorUser['id']]);
                    if (empty($videoFindResult) || empty($videoResult)) {
                        $model->rollback();
                        return apiError(config('response.msg11'));
                    }
                }
            } else {
                $anchorUserResult = $anchorUserModel->insertGetId($anchorUserData);
                if (empty($anchorUserResult)) {
                    $model->rollback();
                    return apiError(config('response.msg11'));
                }
                // 更新数据
                $videoResult = $videoModel->where($videoWhere)->update(['anchor_id' => $anchorUserResult]);
                if (empty($videoResult)) {
                    $model->rollback();
                    return apiError(config('response.msg11'));
                }
            }
            // 用户模型
            $userModel = model(UserModel::class);
            $userData = [
                'gender' => $paramsArray['gender'],
                'adcode' => $paramsArray['adcode'],
                'location' => $paramsArray['location'],
                'longitude' => $paramsArray['longitude'],
                'latitude' => $paramsArray['latitude']
            ];
            // 实例化区域表模型
            $provinceModel = model(AreaModel::class);
            $cityArray = [
                'adcode' => $paramsArray['adcode']
            ];
            // 获取省市区id
            $cityIdArray = $provinceModel->getCityIdByAdcode($cityArray);
            if (!empty($cityIdArray)) {
                $userData['province_id'] = $cityIdArray['provinceId'];
                $userData['city_id'] = $cityIdArray['cityId'];
                $userData['area_id'] = $cityIdArray['areaId'];
            }
            $userModel->where(['id' => $userId])->update($userData);

            // 提交事务
            $model->commit();
            return apiSuccess();
        } catch (\Exception $e) {
            // 回滚事务
            isset($model) && $model->rollback();
            generateApiLog('申请接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 获取实名认证状态信息
     *
     * @return Json
     */
    public function getIdentityCheckStatus()
    {
        try {
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 用户模型
            $userModel = model(UserModel::class);
            $user = $userModel->where(['id' => $userId])->find();
            if (empty($user)) {
                return apiError(config('response.msg9'));
            }
            $identityCheckInfo = [
                'check_status' => $user['identity_check_status'],
                'check_result_reason' => $user['check_result_reason'],
                'nickname' => $user['nickname'],
                'avatar' => getImgWithDomain($user['avatar']),
                'thumb_avatar' => getImgWithDomain($user['thumb_avatar']),
                'real_name' => $user['real_name'],
                'id_number' => $user['identity_check_status'] == 3 ? $user['id_number'] : StringHelper::hidePartOfString($user['id_number'], 1, 1, '****************'),
                'identity_card_holder_half_img' => getImgWithDomain($user['identity_card_holder_half_img']),
                'identity_card_back_face_img' => getImgWithDomain($user['identity_card_back_face_img'])
            ];
            $data = [
                'identity_check_info' => $identityCheckInfo
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('获取实名认证状态信息接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 上传身份证件照片接口
     *
     * @return Json
     */
    public function uploadIdentityImg()
    {
        $uploadImage = input('file.image');
        try {
            $uploadService = new UploadService();
            $uploadResult = $uploadService->setConfig([
                'image_upload_path' => config('parameters.user_identity_card_image_upload_path'),
                'image_thumb_name_type' => 2
            ])->uploadImage($uploadImage);
            if (!$uploadResult['code']) {
                return apiError($uploadResult['msg']);
            }

            return apiSuccess(['image' => $uploadResult['data']['image']]);
        } catch (\Exception $e) {
            generateApiLog('上传身份证件照片接口异常：' . $e->getMessage());
        }

        return apiError();
    }

    /**
     * 实名认证
     *
     * @return Json
     */
    public function identityCheck()
    {
        try {
            // 请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(BlindDateValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('identityCheck')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 用户模型
            $userModel = model(UserModel::class);
            $user = $userModel->where(['id' => $userId])->find();
            if (in_array($user['identity_check_status'], [1, 2])) {
                return apiError(config('response.msg109'));
            }
            // 时间
            $date = date('Y-m-d H:i:s');
            $userData = [
                'real_name' => $paramsArray['real_name'],
                'id_number' => $paramsArray['id_number'],
                'identity_card_holder_half_img' => $paramsArray['identity_card_holder_half_img'],
                'identity_card_back_face_img' => $paramsArray['identity_card_back_face_img'],
                'identity_check_status' => 1,
                'identity_check_apply_time' => $date
            ];

            $userModel->where(['id' => $userId])->update($userData);
            return apiSuccess();
        } catch (\Exception $e) {
            generateApiLog('实名认证接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 我发起的视频记录
     *
     * @return Json
     */
    public function iInitiatedVideoRecord()
    {
        try {
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 请求参数
            $paramsArray = input();
            // 直播数据模型
            $liveShowModel = model(LiveShowModel::class);
            $condition = [
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_2'),
                'userId' => $userId
            ];
            // 获取我发起的直播记录
            $liveArray = $liveShowModel->getMyLiveRecord($condition);
            $liveList = [];
            if (!empty($liveArray)) {
                foreach ($liveArray as $value) {
                    $info = [
                        'start_time' => $value['start_time'],
                        'duration' => ceil((strtotime($value['end_time']) - strtotime($value['start_time']))/60),
                        'anchor_user_id' => $value['anchor_user_id'],
                        'nickname' => $value['nickname'],
                        'thumb_image' => getImgWithDomain($value['thumb_avatar'])
                    ];
                    $liveList[] = $info;
                }
            }
            $data = [
                'live_list' => $liveList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('我发起的视频记录接口异常：' . $e->getMessage());
        }
        return apiError();
    }

    /**
     * 他人向我发起的视频记录
     *
     * @return Json
     */
    public function otherInitiatedVideoRecord()
    {
        try {
            $userId = $this->getUserId();
            if (empty($userId)) {
                return apiError(config('response.msg9'));
            }
            // 请求参数
            $paramsArray = input();
            // 直播数据模型
            $liveShowModel = model(LiveShowModel::class);
            $condition = [
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_2'),
                'userId' => $userId
            ];
            // 获取他人向我发起的视频记录
            $liveArray = $liveShowModel->getOtherLiveRecord($condition);
            $liveList = [];
            if (!empty($liveArray)) {
                foreach ($liveArray as $value) {
                    $info = [
                        'start_time' => $value['start_time'],
                        'duration' => ceil((strtotime($value['end_time']) - strtotime($value['start_time']))/60),
                        'send_user_id' => $value['send_user_id'],
                        'nickname' => $value['nickname'],
                        'thumb_image' => getImgWithDomain($value['thumb_avatar'])
                    ];
                    $liveList[] = $info;
                }
            }
            $data = [
                'live_list' => $liveList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            generateApiLog('他人向我发起的视频记录接口异常：' . $e->getMessage());
        }
        return apiError();
    }
}