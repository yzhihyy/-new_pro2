<?php

namespace app\api\controller\v3_0_0\user;

use app\api\Presenter;
use app\common\utils\string\StringHelper;
use app\api\model\v3_0_0\{
    ThemeArticleModel
};
use app\api\logic\v3_0_0\user\ThemeActivityLogic;

class ThemeArticle extends Presenter
{
    /**
     * 主题文章和活动列表（发现-推荐页面）
     * @return json
     */
    public function themeArticleAndActivityList()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            $page = empty($paramsArray['page']) ? 0 : $paramsArray['page'];
            // 实例化主题文章模型
            $themeArticleModel = model(ThemeArticleModel::class);
            $condition = [
                'page' => $page,
                'limit' => config('parameters.page_size_level_3'),
                'isRecommend' => 1
            ];
            // 获取主题文章列表
            $themeArticleArray = $themeArticleModel->getThemeArticleList($condition);
            $themeArticleList = [];
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
                    $themeArticleList[] = $info;
                }
            }
            $params['page'] = $page;
            $params['limit'] = config('parameters.page_size_level_1');
            $params['theme_status'] = [2, 3];//进行中和已结束
            $themeActivityLogic = new ThemeActivityLogic();
            $themeActivityList = $themeActivityLogic->themeActivityList($params);
            $data = [
                'theme_article_list' => $themeArticleList,
                'theme_activity_list' => $themeActivityList,
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '主题文章和活动列表异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}
