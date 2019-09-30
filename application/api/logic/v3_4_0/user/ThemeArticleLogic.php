<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/9
 * Time: 11:32
 */

namespace app\api\logic\v3_4_0\user;

use app\api\logic\BaseLogic;
use app\api\model\v3_4_0\ThemeArticleModel;

class ThemeArticleLogic extends BaseLogic
{
    public static function themeArticleList($params)
    {
        $model = model(ThemeArticleModel::class);
        $themeArticleArray = $model->getThemeArticleList($params);
        $list = [];
        if (!empty($themeArticleArray)) {
            foreach ($themeArticleArray as $value) {
                $info = [
                    'theme_article_id' => $value['artId'],
                    'theme_article_title' => $value['title'],
                    'theme_article_desc' => $value['title'],
                    'theme_article_cover' => getImgWithDomain($value['cover']),
                    'shop_id' => $value['shopId'],
                    'shop_name' => $value['shopName'],
                    'shop_thumb_image' => getImgWithDomain($value['shopThumbImage']),
                    'theme_id' => $value['themeId'],
                ];
                $list[] = $info;
            }
        }
        return $list;
    }
}