<?php

namespace app\api\controller\v3_4_0\user;

use app\api\logic\v3_4_0\user\ThemeActivityLogic;
use app\api\Presenter;
use app\api\validate\v3_4_0\ThemeValidate;


class Theme extends Presenter
{
    /**
     * 活动主题列表（发现 - 主题）
     * @return \think\response\Json
     */
    public function themeList()
    {
        try{
            $request = $this->request->get();
            if(!isset($request['city_id']) || empty($request['city_id']) || !is_numeric($request['city_id'])){
                return apiError('参数错误');
            }
            $params = [];
            $params['city_id'] = $request['city_id'];
            $params['page'] = $request['page'] ?? 0;
            $params['limit'] = config('parameters.page_size_level_1');
            $params['theme_status'] = [2, 3];//进行中和已结束
            $new = new ThemeActivityLogic();
            $list = $new->themeActivityList($params);
            $info = [
                'list' => $list,
            ];
            return apiSuccess($info);

        }catch (\Exception $e){
            generateApiLog("发现主题接口异常: {$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 发现-主题进行中无城市
     * @return \think\response\Json
     */
    public function themeIngList()
    {
        try{
            $request = $this->request->get();
            $params = [];
            $params['page'] = $request['page'] ?? 0;
            $params['limit'] = config('parameters.page_size_level_1');
            $params['theme_status'] = [2];//进行中和已结束
            $new = new ThemeActivityLogic();
            $list = $new->themeActivityList($params);
            $info = [
                'list' => $list,
            ];
            return apiSuccess($info);

        }catch (\Exception $e){
            generateApiLog("发现主题进行中接口异常: {$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 参与主题的商家列表
     * @return \think\response\Json
     */
    public function themeShopList()
    {
        try{
            $params = $this->request->get();
            $result = $this->validate($params, ThemeValidate::class.'.ShopList');
            if ($result !== true) {
                return apiError($result);
            }
            $params['page'] = $params['page'] ?? 0;
            $params['limit'] = config('parameters.page_size_level_1');

            $new = new ThemeActivityLogic();
            $list = $new->themeActivityShopList($params);
            $info = [
                'list' => $list,
            ];
            return apiSuccess($info);

        }catch (\Exception $e){
            generateApiLog("主题商家列表接口异常: {$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 搜索页3个主题
     * @return \think\response\Json
     */
    public function searchPageThemeList()
    {
        $request = $this->request->get();
        $params = [];
        $params['page'] = $request['page'] ?? 0;
        $params['limit'] = config('parameters.page_size_level_1');
        $params['theme_status'] = [2];//进行中的
        try{
            $new = new ThemeActivityLogic();
            $list = $new->themeActivityList($params);
            $info = [
                'list' => $list,
            ];
            return apiSuccess($info);

        }catch (\Exception $e){
            generateApiLog("主题投票页接口异常: {$e->getMessage()}");
        }
        return apiError();
    }

    /**
     * 主题文章评论列表
     *
     * @return Json
     */
    public function articleCommentList()
    {
        // 获取请求参数
        $paramsArray = $this->request->get();
        $page = $this->request->get('page/d', 0);
        // 实例化验证器
        $validate = validate(ThemeValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('articleCommentList')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $videoLogic = new ThemeActivityLogic();
            $result = $videoLogic->articleCommentList($paramsArray['article_id'], $page);

            return apiSuccess($result);
        } catch (\Exception $e) {
            $logContent = '主题文章评论列表接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 主题文章评论点赞
     *
     * @return \think\response\Json
     */
    public function articleCommentLike()
    {
        $user = $this->request->user;
        // 获取请求参数
        $paramsArray = $this->request->post();
        // 实例化验证器
        $validate = validate(ThemeValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('articleCommentLike')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $themeLogic = new ThemeActivityLogic();
            $result = $themeLogic->articleCommentLike($user, $paramsArray['comment_id']);

            if (!empty($result['msg'])) {
                return apiError($result['msg']);
            }

            return apiSuccess($result['data']);
        } catch (\Exception $e) {
            $logContent = '主题文章评论点赞接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 主题文章评论
     *
     * @return \think\response\Json
     */
    public function articleComment()
    {
        $user = $this->request->user;
        // 获取请求参数
        $paramsArray = $this->request->post();
        // 实例化验证器
        $validate = validate(ThemeValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('articleComment')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $themeLogic = new ThemeActivityLogic();
            $result = $themeLogic->articleComment($user, $paramsArray);

            if (!empty($result['msg'])) {
                return apiError($result['msg']);
            }

            return apiSuccess($result['data']);
        } catch (\Exception $e) {
            $logContent = '主题文章评论接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }

    /**
     * 投票
     *
     * @return \think\response\Json
     */
    public function vote()
    {
        $user = $this->request->user;
        // 获取请求参数
        $paramsArray = $this->request->post();
        // 实例化验证器
        $validate = validate(ThemeValidate::class);
        // 验证请求参数
        $checkResult = $validate->scene('vote')->check($paramsArray);
        if (!$checkResult) {
            // 验证失败提示
            return apiError($validate->getError());
        }

        try {
            $themeLogic = new ThemeActivityLogic();
            $result = $themeLogic->vote($user, $paramsArray);

            if (!empty($result['msg'])) {
                if (is_array($result['msg'])) {
                    return apiError(end($result['msg']), reset($result['msg']));
                }
                return apiError($result['msg']);
            }

            return apiSuccess($result['data']);
        } catch (\Exception $e) {
            $logContent = '主题投票接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }

        return apiError();
    }
}
