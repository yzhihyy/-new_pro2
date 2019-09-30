<?php

namespace app\api\logic\v3_0_0;

use app\api\logic\BaseLogic;
use app\api\logic\v3_0_0\user\FollowLogic;
use app\api\logic\v3_0_0\user\PushMsgLogic;
use app\api\model\v3_0_0\{
    FollowRelationModel, UserDataStatisticsModel, UserMessageModel, UserModel, VideoActionModel,
    VideoModel, ShopModel
};
use app\common\utils\date\DateHelper;
use Exception;

class RobotLogic extends BaseLogic
{
    /**
     * 处理用户注册
     *
     * @param int $userId
     *
     * @throws Exception
     */
    public function handleUserRegister(int $userId)
    {
        try {
            $settings = $this->getSettingsByGroup('user_register');
            if (empty($settings) || !$userId) {
                return;
            }

            // 增加关注
            if (isset($settings['follow'])) {
                $this->increaseFollow($settings['follow']['value'], $userId);
            }

            // 增加粉丝量
            if (isset($settings['fans'])) {
                $this->increaseFans($settings['fans']['value'], $userId, 1);
            }
        } catch (Exception $e) {
            generateApiLog("处理用户注册异常：{$e->getMessage()}");
        }
    }

    /**
     * 处理视频发布
     *
     * @param int $videoId
     * @param int $id   用户或店铺ID
     * @param int $type 1:用户,2:店铺
     *
     * @throws Exception
     */
    public function handleVideoRelease(int $videoId, int $id, int $type = 1)
    {
        try {
            $settings = $this->getSettingsByGroup('video_release');
            if (empty($settings) || !$videoId || !$id) {
                return;
            }

            $updateData = [];

            // 增加播放量
            if (isset($settings['play'])) {
                $updateData['play_count'] = $this->getIncreaseRand($settings['play']['value']);
            }

            /** @var VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            // 开启事务
            $videoModel->startTrans();

            // 增加点赞量
            if (isset($settings['like'])) {
                $rand = $this->getIncreaseRand($settings['like']['value']);
                if ($rand > 0) {
                    $updateData['like_count'] = $this->saveUserActionAndMessage($videoId, $rand, $id, $type, 1);
                }
            }

            // 增加转发量
            if (isset($settings['share'])) {
                $rand = $this->getIncreaseRand($settings['share']['value']);
                if ($rand > 0) {
                    $updateData['share_count'] = $this->saveUserActionAndMessage($videoId, $rand, $id, $type, 2);
                }
            }

            if (!empty($updateData)) {
                $videoModel->where(['id' => $videoId])->update($updateData);
            }

            // 增加粉丝量
            if (isset($settings['fans'])) {
                $this->increaseFans($settings['fans']['value'], $id, $type);
            }

            // 提交事务
            $videoModel->commit();

            // 获取粉丝极光ID
            /** @var FollowRelationModel $followRelationModel */
            $followRelationModel = model(FollowRelationModel::class);
            $fansRegistrationIds = $followRelationModel->getFansRegistrationId(['type' => $type, 'id' => $id]);
            $fansRegistrationIds = array_filter($fansRegistrationIds);
            if (!empty($fansRegistrationIds)) {
                if ($type == 1) {
                    $nickname = model(UserModel::class)->where('id', $id)->value('nickname');
                    $msgType = 'user_new_video';
                } else {
                    $nickname = model(ShopModel::class)->where('id', $id)->value('shop_name');
                    $msgType = 'shop_new_video';
                }

                $videos = $videoModel->searchVideo([
                    'followedFlag' => true,
                    'followedOrderFlag' => true,
                    'page' => 0,
                    'limit' => 1
                ]);
                $followLogic = new FollowLogic();
                $pushExtras = [
                    'video_list' => $followLogic->handleVideoList($videos)
                ];
                // 极光推送
                // 未审核不推送
                // $pushMsgLogic = new PushMsgLogic();
                // $pushMsgLogic->pushToUserMsg($fansRegistrationIds, $nickname, $msgType, 0, $pushExtras);
            }
        } catch (Exception $e) {
            // 事务回滚
            isset($videoModel) && $videoModel->rollback();
            generateApiLog("处理视频发布异常：{$e->getMessage()}");
        }
    }

    /**
     * 处理关注他人
     *
     * @param int $userId
     *
     * @throws Exception
     */
    public function handleFollowOthers(int $userId)
    {
        try {
            $settings = $this->getSettingsByGroup('follow_others');
            if (empty($settings) || !$userId) {
                return;
            }

            // 增加粉丝量
            if (isset($settings['fans'])) {
                $this->increaseFans($settings['fans']['value'], $userId, 1);
            }
        } catch (Exception $e) {
            generateApiLog("处理关注他人异常：{$e->getMessage()}");
        }
    }

    /**
     * 处理分享视频
     *
     * @param int $userId
     *
     * @throws Exception
     */
    public function handleVideoShare(int $userId)
    {
        try {
            $settings = $this->getSettingsByGroup('video_share');
            if (empty($settings) || !$userId) {
                return;
            }

            // 增加粉丝量
            if (isset($settings['fans'])) {
                $this->increaseFans($settings['fans']['value'], $userId, 1);
            }
        } catch (Exception $e) {
            generateApiLog("处理分享视频异常：{$e->getMessage()}");
        }
    }

    /**
     * 处理视频或话题浏览
     *
     * @param int $userId
     * @param int $type 1:视频浏览, 2:话题浏览
     * @param int $playCount
     *
     * @throws \think\exception\PDOException
     */
    public function handleVideoOrTopicView(int $userId, int $type, int $playCount)
    {
        try {
            // 当前时间
            $nowTime = DateHelper::getNowDateTime();
            /** @var UserDataStatisticsModel $userDataStatisticsModel */
            $userDataStatisticsModel = model(UserDataStatisticsModel::class);
            // 获取用户今天的视频播放或话题浏览记录
            $dataStatistics = $userDataStatisticsModel->getUserDataStatistics([
                'type' => $type,
                'userId' => $userId,
                'specifiedDate' => $nowTime->format('Y-m-d'),
            ]);
            $data = [
                'type' => $type,
                'user_id' => $userId,
                'count' => ($dataStatistics[0]['count'] ?? 0) + $playCount,
            ];

            // 开启事务
            $userDataStatisticsModel->startTrans();

            if (empty($dataStatistics) || !$dataStatistics[0]['useFlag']) {
                $settingKey = $type == 1 ? 'video_view' : 'topic_view';
                $settings = $this->getSettingsByGroup($settingKey);
                if (empty($settings)) {
                    return;
                }

                $valueArray = json_decode($settings['view']['value'], true);
                // 浏览次数
                $countLimit = $valueArray['count'];
                // 满足浏览次数,则涨粉
                if ($countLimit > 0 && $data['count'] >= $countLimit) {
                    $this->increaseFans(implode(',', $valueArray['fans']), $userId, 1);
                    $data['use_flag'] = 1;
                }
            }

            if (empty($dataStatistics)) {
                $data['generate_time'] = $nowTime->format('Y-m-d H:i:s');
                $userDataStatisticsModel::create($data);
            } else {
                $userDataStatisticsModel->where(['id' => $dataStatistics[0]['statisticsId']])->update($data);
            }

            // 提交事务
            $userDataStatisticsModel->commit();
        } catch (Exception $e) {
            // 事务回滚
            isset($userDataStatisticsModel) && $userDataStatisticsModel->rollback();
            generateApiLog("处理视频或话题浏览异常：{$e->getMessage()}");
        }
    }

    /**
     * 增加关注
     *
     * @param string $value
     * @param int    $userId
     *
     * @throws Exception
     */
    private function increaseFollow(string $value, int $userId)
    {
        $rand = $this->getIncreaseRand($value);
        if ($rand > 0) {
            /** @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $robotUsers = $userModel->getRobotUsers([
                'type' => 2,
                'userId' => $userId,
                'limit' => $rand,
            ]);
            $this->saveUserFollowRelation($robotUsers, 3, $userId);
        }
    }

    /**
     * 增加粉丝量
     *
     * @param string $value
     * @param int    $id   用户或店铺ID
     * @param int    $type 1:用户,2:店铺
     *
     * @throws Exception
     */
    private function increaseFans(string $value, int $id, int $type)
    {
        $rand = $this->getIncreaseRand($value);
        if ($rand > 0) {
            $condition = ['limit' => $rand];
            if ($type == 1) {
                $condition['type'] = 3;
                $condition['userId'] = $id;
            } else {
                $condition['type'] = 4;
                $condition['shopId'] = $id;
            }

            /** @var UserModel $userModel */
            $userModel = model(UserModel::class);
            $robotUsers = $userModel->getRobotUsers($condition);
            if (!empty($robotUsers)) {
                $this->saveUserFollowRelation($robotUsers, $type, $id);

                // 极光推送
                $pushMsgLogic = new PushMsgLogic();
                if ($type == 1) {
                    $toUser = model(UserModel::class)->find($id);
                    if ($toUser) {
                        $pushMsgLogic->pushToUserMsg([$toUser->registration_id], $robotUsers[0]['nickname'], 'follow_user', $toUser->id);
                    }
                } else {
                    $pushMsgLogic->pushToShopMsg($id, $robotUsers[0]['nickname'], 'follow_shop');
                }
            }
        }
    }

    /**
     * 保存点赞或转发及消息
     *
     * @param int $videoId
     * @param int $count
     * @param int $id       用户或店铺ID
     * @param int $type     1:用户,2:店铺
     * @param int $operType 1:点赞,2:转发
     *
     * @return int
     * @throws Exception
     */
    private function saveUserActionAndMessage(int $videoId, int $count, int $id, int $type, int $operType)
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        // 获取机器人用户
        $robotUsers = $userModel->getRobotUsers(['limit' => $count]);
        if (!empty($robotUsers)) {
            /** @var VideoActionModel $videoActionModel */
            $videoActionModel = model(VideoActionModel::class);
            $nowTime = date('Y-m-d H:i:s');
            $actionData = $messageData = $tmpData = [];
            $type == 1 ? ($tmpData['to_user_id'] = $id) : ($tmpData['to_shop_id'] = $id);
            foreach ($robotUsers as $user) {
                $actionData[] = [
                    'action_type' => $operType,
                    'video_id' => $videoId,
                    'user_id' => $user['userId'],
                    'status' => 1,
                    'generate_time' => $nowTime
                ];

                if ($operType == 1) {
                    $messageData[] = array_merge([
                        'msg_type' => 3,
                        'video_id' => $videoId,
                        'from_user_id' => $user['userId'],
                        'read_status' => 0,
                        'delete_status' => 0,
                        'generate_time' => $nowTime
                    ], $tmpData);
                }
            }

            $videoActionModel->saveAll($actionData);
            if (!empty($messageData)) {
                /** @var UserMessageModel $userMessageModel */
                $userMessageModel = model(UserMessageModel::class);
                $userMessageModel->saveAll($messageData);

                // 极光实时推送
                $pushMsgLogic = new PushMsgLogic();
                if ($type == 1) {
                    $toUser = model(UserModel::class)->find($id);
                    if ($toUser) {
                        $pushMsgLogic->pushToUserMsg([$toUser->registration_id], $robotUsers[0]['nickname'], 'video_like', $toUser->id);
                    }
                } else {
                    $pushMsgLogic->pushToShopMsg($id, $robotUsers[0]['nickname'], 'video_like');
                }
            }

            return count($actionData);
        }

        return $count;
    }

    /**
     * Get increase rand.
     *
     * @param string $value
     *
     * @return int
     */
    private function getIncreaseRand(string $value)
    {
        list ($min, $max) = explode(',', $value, 2);
        $rand = mt_rand($min, $max);
        if ($rand > 0) {
            return $rand;
        }

        return 0;
    }

    /**
     * 保存用户关注或增粉
     *
     * @param array $robotUsers
     * @param int   $type 1:用户增粉,2:店铺增粉,3:关注
     * @param int   $id
     *
     * @throws Exception
     */
    private function saveUserFollowRelation(array $robotUsers, int $type, int $id)
    {
        $nowTime = date('Y-m-d H:i:s');
        $followRelationData = [
            'rel_type' => 1,
            'generate_time' => $nowTime
        ];
        $userMsgData = [
            'msg_type' => 6, // 新增粉丝消息类型为6
            'read_status' => 0,
            'delete_status' => 0,
            'generate_time' => $nowTime
        ];
        $data = [];
        $msgData = [];
        foreach ($robotUsers as $user) {
            $tmpData = [];
            // 增加关注
            if ($type == 3) {
                $tmpData['from_user_id'] = $id;
                $tmpData['to_user_id'] = $user['userId'];
            }
            // 增加粉丝
            else {
                $tmpData['from_user_id'] = $user['userId'];
                $type == 1 ? ($tmpData['to_user_id'] = $id) : ($tmpData['to_shop_id'] = $id);
            }

            $data[] = array_merge($followRelationData, $tmpData);
            $msgData[] = array_merge($userMsgData, $tmpData);
        }

        if (!empty($data)) {
            /** @var FollowRelationModel $followRelationModel */
            $followRelationModel = model(FollowRelationModel::class);
            $followRelationModel->saveAll($data);
        }
        if (!empty($msgData)) {
            /** @var UserMessageModel $userMessageModel */
            $userMessageModel = model(UserMessageModel::class);
            $userMessageModel->saveAll($msgData);
        }
    }
}
