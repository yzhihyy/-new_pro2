<?php

namespace app\api\logic\v3_0_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_0_0\FollowRelationModel;
use app\api\model\v3_0_0\UserModel;

class ContactsLogic extends BaseLogic
{
    /**
     * 查询好友关系
     *
     * @param int $userId
     * @param array $contactList
     * @return array
     *
     * @throws \Exception
     */
    public function queryFriendRelationship(int $userId, array $contactList)
    {
        /** @var UserModel $userModel */
        $userModel = model(UserModel::class);
        $friendRelationshipList = $userModel->queryFriendRelationship([
            'userId' => $userId,
            'phones' => array_column($contactList, 'phone'),
        ]);
        $friendRelationshipList = array_column($friendRelationshipList, null, 'phone');
        // 关注指定用户
        $this->followSpecifiedUsers($userId, $friendRelationshipList);

        $response = [];
        $fields = [
            'is_register',
            'user_id',
            'nickname',
            'phone',
            'name',
            'avatar',
            'video_count',
            'fans_count',
            'like_count',
            'is_follow',
        ];
        foreach ($contactList as $contact) {
            $user = $friendRelationshipList[$contact['phone']] ?? [];
            $name = $contact['name'] ?? '';
            if (!empty($user)) {
                $response[] = array_combine($fields, [
                    1,
                    $user['userId'],
                    $user['nickname'],
                    $user['phone'],
                    $name,
                    getImgWithDomain($user['thumbAvatar']),
                    $user['videoCount'],
                    $user['fansCount'],
                    $user['likeCount'],
                    // TODO 当前版本自动关注(除了用户自主取消关注)
                    !$user['isFollow'] && !empty($user['followId']) ? 0 : 1,
                ]);
            } else {
                $response[] = array_combine($fields, [0, 0, '', $contact['phone'], $contact['name'], '', 0, 0, 0, 0]);
            }
        }

        return $response;
    }
    
    /**
     * 关注指定用户
     *
     * @param int $userId
     * @param array $users
     *
     * @throws \Exception
     */
    public function followSpecifiedUsers(int $userId, array $users)
    {
        $followRelationModel = model(FollowRelationModel::class);
        if (!empty($users)) {
            $followData = [];
            foreach ($users as $user) {
                if ($user['userId'] != $userId && empty($user['followId'])) {
                    // 防止重复数据
                    $data = $followRelationModel->where([
                        'from_user_id' => $userId,
                        'to_user_id' => $user['userId'],
                    ])->find();
                    if (empty($data)) {
                        $followData[] = [
                            'rel_type' => 1,
                            'from_user_id' => $userId,
                            'to_user_id' => $user['userId'],
                            'generate_time' => date('Y-m-d H:i:s')
                        ];
                    }
                }
            }
            if (!empty($followData)) {
                $followRelationModel->saveAll($followData);
            }
        }
    }
}