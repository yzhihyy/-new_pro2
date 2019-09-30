<?php

namespace app\api\logic\v1_1_0\shop;

use app\api\logic\BaseLogic;

class PhotoAlbum extends BaseLogic
{
    /**
     * 店铺活动相册分组
     *
     * @param array $photoAlbum
     *
     * @return array
     */
    public function groupShopActivityPhotoAlbum($photoAlbum)
    {
        $return = [];
        foreach ($photoAlbum as $item) {
            $info = [];
            $info['name'] = $item['name'];
            $info['image'] = getImgWithDomain($item['image']);
            $info['thumbImage'] = getImgWithDomain($item['thumbImage']);
            $activityId = $item['activityId'];
            $return[$activityId][] = $info;
        }
        return $return;
    }
}
