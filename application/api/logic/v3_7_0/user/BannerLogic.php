<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 16:29
 */

namespace app\api\logic\v3_7_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v2_0_0\BannerModel;

class BannerLogic extends BaseLogic
{
    /**
     * 获取首页banner
     * @param array $params
     * @return array
     */
    public static function getBannerList(array $params = []): array
    {
        $list = model(BannerModel::class)
            ->field(['id AS banner_id', 'image', 'thumb_image', 'ad_link_type', 'ad_link'])
            ->where(['status' => 1]);

        if(!empty($params) && isset($params['position'])){
            $list->where('position', $params['position']);
        }
        $list = $list->order('sort', 'desc')->limit(0, 6)->select()->toArray();

        foreach($list as $key => &$value){
            $value['image'] = getImgWithDomain($value['image']);
            $value['thumb_image'] = getImgWithDomain($value['thumb_image']);
        }

        return $list ?: [];
    }
}