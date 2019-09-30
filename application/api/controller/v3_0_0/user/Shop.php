<?php

namespace app\api\controller\v3_0_0\user;

use app\api\Presenter;
use app\api\model\v3_0_0\{ThemeActivityShopModel, ThemeArticleModel, VideoModel, PhotoAlbumModel, ShopModel};
use app\api\validate\v3_0_0\{
    ShopValidate
};
use app\common\utils\string\StringHelper;
use app\api\logic\shop\SettingLogic;

class Shop extends Presenter
{
    /**
     * 获取店铺视频列表.
     *
     * @return \think\response\Json
     */
    public function getShopVideoList()
    {
        try {
            // TODO APP审核时不返还视频
            $reviewDisplayRule = config('parameters.shop_detail_video_display_rule_for_review');
            if ($reviewDisplayRule['enable_flag']) {
                $platform = $this->request->header('platform');
                $version = $this->request->header('version');
                if (in_array($platform, $reviewDisplayRule['platform']) && version_compare($version, $reviewDisplayRule['version']) == 0) {
                    $responseData = [
                        'shop_video_list' => []
                    ];
                    return apiSuccess($responseData);
                }
            }
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 店铺ID
            $shopId = input('shop_id/d', 0);
            if ($shopId <= 0) {
                return apiError(config('response.msg10'));
            }
            // 获取商家视频列表
            $where = [
                'shopId' => $shopId,
                'page' => $pageNo,
                'limit' => $perPage
            ];
            $userId = $this->getUserId();
            if ($userId) {
                $where['userId'] = $userId;
            }
            /** @var \app\api\model\v3_0_0\VideoModel $videoModel */
            $videoModel = model(VideoModel::class);
            $shopVideoList = $videoModel->getShopVideoList($where);
            // 返回数据
            $shopVideoArray = [];
            // 店铺设置逻辑
            $settingLogic = new SettingLogic();
            foreach ($shopVideoList as $value) {
                $item = [];
                $item['video_id'] = $value['videoId'];
                $item['video_user_id'] = $value['videoUserId'];
                $item['title'] = $value['videoTitle'];
                $item['video_url'] = $value['videoUrl'];
                $item['cover_url'] = $value['coverUrl'];
                $item['like_count'] = $value['likeCount']; // 点赞数量
                $item['comment_count'] = $value['commentCount']; // 评论数量
                $item['share_count'] = $value['shareCount']; // 转发数量
                $item['shop_id'] = $value['shopId']; // 店铺ID
                $item['shop_name'] = $value['shopName']; // 店铺名称
                $item['shop_address'] = $value['shopAddress']; // 店铺名称
                $item['qq'] = $value['qq']; // qq
                $item['wechat'] = $value['wechat']; // wechat
                // 店铺设置转换
                $setting = $settingLogic->settingTransform($value['setting']);
                $item['show_send_sms'] = $setting['show_send_sms'];
                $item['show_phone'] = $setting['show_phone'];
                $item['show_enter_shop'] = $setting['show_enter_shop'];
                $item['show_address'] = $setting['show_address'];
                $item['show_wechat'] = $setting['show_wechat'];
                $item['show_qq'] = $setting['show_qq'];
                $item['shop_image'] = getImgWithDomain($value['shopImage']); // 店铺图片
                $item['shop_thumb_image'] = getImgWithDomain($value['shopThumbImage']); // 店铺缩略图
                $item['is_follow'] = ($userId == $value['videoUserId']) ? 1 : ($value['isFollow'] ?? 0); // 是否关注
                $item['is_like'] = $value['isLike'] ?? 0; // 是否点赞
                $item['video_width'] = $value['videoWidth']; // 视频宽度
                $item['video_height'] = $value['videoHeight']; // 视频高度
                // 分享链接&&缩略图
                $item['share_url'] = config('app_host') . '/h5/v3_0_0/videoDetail.html?video_id=' . $value['videoId'];
                $item['share_image'] = $value['coverUrl'];
                $item['is_top'] = $value['isTop'];
                array_push($shopVideoArray, $item);
            }
            $responseData = [
                'shop_video_list' => $shopVideoArray
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取商家推荐列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 获取相册列表.
     *
     * @return \think\response\Json
     */
    public function getShopPhotoAlbumList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 店铺ID
            $shopId = input('shop_id/d', 0);
            if ($shopId <= 0) {
                return apiError(config('response.msg10'));
            }
            // 获取商家相册列表
            $where = [
                'page' => $pageNo,
                'limit' => $perPage,
                'shopId' => $shopId,
                'type' => 1,
            ];
            /** @var \app\api\model\v3_0_0\PhotoAlbumModel $photoAlbumModel */
            $photoAlbumModel = model(PhotoAlbumModel::class);
            $shopPhotoAlbum = $photoAlbumModel->getShopPhotoAlbum($where);
            $shopPhotoAlbumCount = $photoAlbumModel->countShopPhotoAlbum($where);
            // 返回数据
            $shopPhotoAlbumList = [];
            foreach ($shopPhotoAlbum as $info) {
                $item = [];
                $item['photo_id'] = $info['id'];
                $item['name'] = $info['name'];
                $item['image'] = getImgWithDomain($info['image']);
                $item['thumb_image'] = getImgWithDomain($info['thumbImage']);
                array_push($shopPhotoAlbumList, $item);
            }
            $responseData = [
                'shop_photo_album_list' => $shopPhotoAlbumList,
                'shop_photo_album_count' => $shopPhotoAlbumCount,
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取商家推荐列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();

    }

    /**
     * 获取店铺精讲列表.
     *
     * @return \think\response\Json
     */
    public function getShopArticleList()
    {
        try {
            // 页码
            $pageNo = input('page/d', 0);
            $pageNo < 0 && ($pageNo = 0);
            // 分页数量
            $perPage = input('per_page/d', 0);
            $perPage <= 0 && ($perPage = config('parameters.page_size_level_2'));
            // 店铺ID
            $shopId = input('shop_id/d', 0);
            if ($shopId <= 0) {
                return apiError(config('response.msg10'));
            }
            // 获取商家精讲列表
            $themeArticleModel = model(ThemeArticleModel::class);
            $condition = [
                'page' => $pageNo,
                'limit' => $perPage,
                'shopId' => $shopId,
                'formShopDetail' => 1
            ];
            $themeArticleArray = $themeArticleModel->getThemeArticleList($condition);
            // 返回精讲列表
            $themeArticleList = [];
            if (!empty($themeArticleArray)) {
                foreach ($themeArticleArray as $value) {
                    // 获取该文章最后参加的主题
                    $theme = model(ThemeActivityShopModel::class)->getThemeByArticle(['articleId' => $value['artId']]);
                    $info = [
                        'theme_id' => $theme ? $theme->id : 0, // 主题id，如果改文章参加多个主题，显示最后一个参加的主题
                        'theme_article_id' => $value['artId'],
                        'theme_article_title' => $value['title'],
                        'theme_article_desc' => $value['title'],
                        'theme_article_cover' => getImgWithDomain($value['cover']),
                        'theme_article_generate_time' => $value['generateTime'],
                    ];
                    $themeArticleList[] = $info;
                }
            }
            // 响应数据
            $responseData = [
                'theme_article_list' => $themeArticleList
            ];
            return apiSuccess($responseData);
        } catch (\Exception $e) {
            $logContent = '获取商家精讲列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 获取商家信息
     *
     * @return Json
     */
    public function getShopInfo()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate(ShopValidate::class);
            // 验证请求参数
            $checkResult = $validate->scene('getShopInfo')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            $shopId = $paramsArray['shop_id'];
            $shopModel = model(ShopModel::class);
            $where = [
                'id' => $shopId
            ];
            // 查询店铺信息
            $shopInfo = $shopModel->getShopInfo($where);
            if (empty($shopInfo)) {
                // 商家不存在或被禁用！
                return apiError(config('response.msg10'));
            }
            $data = [
                'pay_setting_type' => $shopInfo['pay_setting_type'],
                'prestore_money' => $shopInfo['prestore_money'],
                'present_money' => $shopInfo['present_money'],
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '获取商家信息接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}
