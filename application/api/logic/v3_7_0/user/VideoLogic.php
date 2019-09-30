<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 18:49
 */

namespace app\api\logic\v3_7_0\user;



use app\api\model\v3_4_0\CityModel;
use app\api\model\v3_6_0\VideoModel;

class VideoLogic extends BannerLogic
{
    /**
     * 相亲视频详情
     * @param array $params
     * @return mixed
     * @throws
     */
    public static function getAnchorVideoDetail(array $params)
    {
        $info = model(VideoModel::class)->alias('v')
            ->field([
                'v.id AS video_id',
                'v.cover_url',
                'v.video_url',
                'v.video_width',
                'v.video_height',
                'v.share_count',
                'v.title',
                'u.id AS user_id',
                'u.nickname',
                'u.city_id',
                'u.thumb_avatar AS avatar',
                'u.gender',
                'u.age',
                'su.user_id AS social_user_id',
                'su.hx_uuid AS social_hx_uuid',
                'su.hx_username AS social_hx_username',
                'su.hx_nickname AS social_hx_nickname',
                'su.hx_password AS social_hx_password',
                "(GLength(GeomFromText(CONCAT('LineString({$params['latitude']} {$params['longitude']},',u.latitude,' ',u.longitude,')')))/0.0000092592666666667) as distance",
            ])
            ->join('user u', 'u.id = v.anchor_id')
            ->join('social_user su', 'su.user_id = u.id')
            ->where('v.id', $params['video_id'])
            ->find()
            ->toArray();

        if(!empty($info)){
            $info['avatar'] = getImgWithDomain($info['avatar']);
            $info['city_desc'] = model(CityModel::class)->where('id', $info['city_id'])->value('name') ?? '';
            $info['distance'] = (new self())->showDistance($info['distance']);
            unset($info['city_id']);
        }
        return $info;
    }
}