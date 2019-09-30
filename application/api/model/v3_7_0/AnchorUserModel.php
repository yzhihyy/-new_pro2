<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 18:51
 */
namespace app\api\model\v3_7_0;

use app\common\model\AbstractModel;

class AnchorUserModel extends AbstractModel
{
    protected $name = 'anchor_user';

    /**
     * 获取主播列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function anchorList(array $where = []): array
    {
        $query = $this->alias('au')
            ->field([
                'au.id AS anchor_id',
                'au.user_id AS anchor_user_id',
                'au.user_id',
                'u.city_id',
                'u.nickname',
                'u.introduction',
                'u.thumb_avatar AS avatar',
                'u.gender',
                'u.age',
                'v.id AS video_id',
                'v.cover_url',
                'v.video_url',
                'v.video_width',
                'v.video_height',
                'v.share_count',
                'v.title',
                'v.type',
                'su.user_id AS social_user_id',
                'su.hx_uuid AS social_hx_uuid',
                'su.hx_username AS social_hx_username',
                'su.hx_nickname AS social_hx_nickname',
                'su.hx_password AS social_hx_password',

            ])
            ->join('user u', 'u.id = au.user_id')
            ->join('video v', 'v.anchor_id = au.id')
            ->join('social_user su', 'su.user_id = au.user_id')
            ->where([
                'au.status' => 1,
                'u.is_robot' => 0,
                'u.account_status' => 1,
                'v.visible' => 1,
                'v.audit_status' => 1,
                'v.status' => 1,
            ]);

        if(isset($where['latitude']) && isset($where['longitude']) && !empty($where['latitude']) && !empty($where['longitude'])){
            $query->field("(GLength(GeomFromText(CONCAT('LineString({$where['latitude']} {$where['longitude']},',u.latitude,' ',u.longitude,')')))/0.0000092592666666667) as distance");
        }

        if(isset($where['gender']) && !empty($where['gender'])){
            $query->where('u.gender', $where['gender']);
        }

        if($where['type'] == 0){
            $query->order('distance', 'ASC');
        }elseif($where['type'] == 1){
            $query->order('call_completing_rate', 'DESC');
        }

        return $query->limit($where['page'] * $where['limit'], $where['limit'])->select()->toArray();
    }
}