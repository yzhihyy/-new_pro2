<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/8
 * Time: 9:49
 */
namespace app\api\logic\v3_4_0\user;
use app\api\logic\BaseLogic;
use app\api\model\v3_4_0\ThemeArticleModel;


class SearchLogic extends BaseLogic
{

    /**
     * 搜索主题列表
     * @param $params
     * @return mixed
     */
    public static function searchThemeList($params)
    {
        $where = [];
        $where['page'] = $params['page'] ?? 0;
        $where['limit'] = $params['limit'];
        $where['theme_status'] = [2, 3];//进行中和结束
        $where['keyword'] = $params['keyword'];
        $new = new ThemeActivityLogic();
        $list = $new->themeActivityList($where);
        return $list;
    }

    /**
     * 搜索文章
     * @param $params
     * @return mixed
     */
    public static function searchThemeArticleList($params)
    {
        $where = [];
        $where['page'] = $params['page'] ?? 0;
        $where['limit'] = $params['limit'];
        $where['keyword'] = $params['keyword'];
        $query = model(ThemeArticleModel::class)->getThemeArticleList($where);
        $list = [];
        if (!empty($query)) {
            foreach ($query as $value) {
                $info = [
                    'theme_article_id' => $value['artId'],
                    'theme_article_title' => $value['title'],
                    'theme_article_desc' => $value['title'],
                    'theme_article_cover' => getImgWithDomain($value['cover']),
                    'shop_id' => $value['shopId'],
                    'shop_name' => $value['shopName'],
                    'shop_thumb_image' => getImgWithDomain($value['shopThumbImage']),
                    'theme_id' => $value['themeId'] ?? '',
                    'theme_type' => $value['themeType'] ?? 1,
                ];
                $list[] = $info;
            }
        }
        return $list;
    }
}