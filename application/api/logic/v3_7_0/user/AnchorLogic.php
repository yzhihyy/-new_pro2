<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 18:49
 */

namespace app\api\logic\v3_7_0\user;

use app\api\model\v3_4_0\CityModel;
use app\api\model\v3_5_0\FollowRelationModel;
use app\api\model\v3_7_0\AnchorUserModel;

class AnchorLogic extends BannerLogic
{
    /**
     * 相亲首页逻辑
     * @param array $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAnchorList(array $params)
    {
        $list = model(AnchorUserModel::class)->anchorList($params);
        if(!empty($list)){
            $cityModel = model(CityModel::class);
            $followModel = model(FollowRelationModel::class);
            $myUserId = (new self())->getUserId();
            foreach($list as $key => &$value){
                $value['city_desc'] = $cityModel->where('id', $value['city_id'])->value('name') ?? '';
                isset($value['distance']) ? $value['distance'] = (new self())->showDistance($value['distance']) : $value['distance'] = '';
                $value['avatar'] = getImgWithDomain($value['avatar']);
                $value['is_online'] = 1;
                $value['is_follow'] = 0;//默认不关注
                //查询是否关注
                if(!empty($myUserId)){
                    $isFollow = $followModel->where(['from_user_id' => $myUserId, 'to_user_id' => $value['user_id'], 'rel_type' => 1])->find();
                    if(!empty($isFollow)){
                        $value['is_follow'] = 1;
                    }
                }

                if(empty($value['introduction'])){
                    $value['introduction'] = '暂无介绍';
                }
                // 分享链接
                if ($value['type'] == 1) {
                    $value['share_url'] = config('app_host'). '/h5/v3_7_0/meetingVideo.html?video_id=' . $value['video_id'];
                } else {
                    $value['share_url'] = config('app_host') . '/h5/v3_5_0/note.html?video_id=' . $value['video_id'];
                }
                unset($value['city_id']);
            }
        }

        return $list ?: [];
    }
}