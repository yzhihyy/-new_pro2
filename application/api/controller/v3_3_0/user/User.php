<?php

namespace app\api\controller\v3_3_0\user;

use app\api\Presenter;
use app\api\model\v3_0_0\UserModel;
use app\api\model\v3_0_0\FollowRelationModel;
use app\api\model\v3_3_0\VideoActionModel;
use app\api\model\v3_3_0\VideoModel;
use app\api\logic\v3_0_0\user\FollowLogic;
use app\common\utils\string\StringHelper;

class User extends Presenter
{
    /**
     * 用户中心.
     *
     * @return \think\response\Json
     */
    public function userCenter()
    {
        try {
            // 获取用户信息
            $userId = $this->getUserId();
            if (empty($userId)) {
                list($code, $msg) = explode('|', config('response.msg9'));
                return apiError($msg, $code);
            }
            $userModel = model(UserModel::class);
            $userInfo = $userModel->find($userId);
            if (empty($userInfo)) {
                list($code, $msg) = explode('|', config('response.msg9'));
                return apiError($msg, $code);
            }
            // 统计我关注的用户数量
            $followModel = model(FollowRelationModel::class);
            $followCount = $followModel->alias('fr')
                ->where([
                    'from_user_id' => $userId,
                    'rel_type' => 1
                ])
                ->count();
            // 统计我的粉丝数量
            $fansCount = $followModel->alias('fr')
                ->where([
                    'to_user_id' => $userId,
                    'rel_type' => 1
                ])
                ->count();
            // 返回消息
            $data = [
                'user_info' => [
                    'user_id' => $userId,
                    'nickname' => $userInfo['nickname'],
                    'avatar' => getImgWithDomain($userInfo['avatar']),
                    'thumb_avatar' => getImgWithDomain($userInfo['thumb_avatar']),
                ],
                'follow_count' => $followCount,
                'fans_count' => $fansCount
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '用户中心接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 我喜欢的作品列表.
     *
     * @return \think\response\Json
     */
    public function getMyJoyVideoList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 类型
            $type = input('type', 0);
            // 获取列表
            $videoActionModel = model(VideoActionModel::class);
            $userId = $this->getUserId();
            $where = [
                'userId' => $userId,
                'page' => $pageNo,
                'limit' => $perPage
            ];
            if ($type) {
                $where['type'] = $type;
            }
            $result = $videoActionModel->getMyJoyVideoList($where);
            $joyVideoList = $result['videoList'];
            // 处理返回数据
            $logic = new FollowLogic();
            $joyVideoList = $logic->handleVideoList($joyVideoList, $userId);
            // 返回数据
            $responseData = [
                'video_count' => $result['totalCount'],
                'video_list' => $joyVideoList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取我喜欢的视频列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }

    /**
     * 我的作品列表.
     *
     * @return \think\response\Json
     */
    public function getMyVideoList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 类型
            $type = input('type', 0);
            // 获取列表
            $videoModel = model(VideoModel::class);
            $userId = $this->getUserId();
            $where = [
                'userId' => $userId,
                'page' => $pageNo,
                'limit' => $perPage
            ];
            if ($type) {
                $where['type'] = $type;
            }
            $result = $videoModel->getMyVideoList($where);
            $videoList = $result['videoList'];
            // 处理返回数据
            $logic = new FollowLogic();
            $videoList = $logic->handleVideoList($videoList, $userId);
            // 返回数据
            $responseData = [
                'video_count' => $result['totalCount'],
                'video_list' => $videoList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取我的作品列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}
