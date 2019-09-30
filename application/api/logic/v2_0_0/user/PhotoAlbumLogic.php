<?php

namespace app\api\logic\v2_0_0\user;

use app\api\logic\BaseLogic;

class PhotoAlbumLogic extends BaseLogic
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
