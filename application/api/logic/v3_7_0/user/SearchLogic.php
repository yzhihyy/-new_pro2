<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/27
 * Time: 16:17
 */
namespace app\api\logic\v3_7_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_5_0\FollowRelationModel;
use app\api\model\v3_5_0\VideoActionModel;
use app\api\model\v3_6_0\VideoModel;
use app\api\model\v3_7_0\UserModel;

class SearchLogic extends BaseLogic
{
    public static function searchByNickname(array $params = []): array
    {
        $where = [];
        $where['page'] = $params['page'] ?? 0;
        $where['limit'] = $params['limit'];
        $where['keyword'] = $params['keyword'];

        $userList = model(UserModel::class)->searchNickname($where)->toArray();

        if(!empty($userList)) {
            foreach ($userList as $key => &$value) {
                $value['avatar'] = getImgWithDomain($value['avatar']);
                $value['social_info'] = [
                    'social_user_id' => $value['social_user_id'] ?: '',
                    'social_hx_uuid' => $value['social_hx_uuid'] ?: '',
                    'social_hx_username' => $value['social_hx_username'] ?: '',
                    'social_hx_nickname' => $value['social_hx_nickname'] ?: '',
                    'social_hx_password' => $value['social_hx_password'] ?: '',
                ];
                $value['video_count'] = static ::getUserVideoCount($value['user_id']);
                $value['fans_count'] = static::getUserFansCount($value['user_id']);
                $value['like_count'] = static::getUserLikeCount($value['user_id']);
                unset($value['social_user_id']);
                unset($value['social_hx_uuid']);
                unset($value['social_hx_username']);
                unset($value['social_hx_nickname']);
                unset($value['social_hx_password']);
            }
        }

        return $userList ?: [];

    }

    /**
     * 获取用户视频数量
     * @param $userId
     * @return float|string
     */
    public static function getUserVideoCount($userId)
    {
        return model(VideoModel::class)->where(['user_id' => $userId, 'visible' => 1, 'status' => 1])->count('id');
    }

    /**
     * 获取用户粉丝数
     * @param $userId
     * @return float|string
     */
    public static function getUserFansCount($userId)
    {
        return model(FollowRelationModel::class)->where(['from_user_id' => $userId, 'rel_type' => 1])->count('id');
    }

    /**
     * 获取用户获赞数
     * @param $userId
     * @return float|string
     */
    public static function getUserLikeCount($userId)
    {
        return model(VideoActionModel::class)->where(['action_type' => 1, 'user_id' => $userId, 'status' => 1])->count('id');
    }
}