<?php

namespace app\api\controller\v3_4_0\user;

use app\api\Presenter;
use app\api\model\v3_0_0\FollowRelationModel;
use app\api\model\v3_0_0\UserModel;
use app\api\model\v3_4_0\UserThemeApplyModel;
use app\api\validate\v3_4_0\UserValidate;
use app\common\utils\string\StringHelper;
use app\api\model\v2_0_0\UserHasShopModel;

class User extends Presenter
{
    /**
     * 用户中心.
     *
     * @return \think\response\Json
     */
    public function userCenter()
    {
        try {
            // 获取用户信息
            $userId = $this->getUserId();
            if (empty($userId)) {
                list($code, $msg) = explode('|', config('response.msg9'));
                return apiError($msg, $code);
            }
            $userModel = model(UserModel::class);
            $userInfo = $userModel->find($userId);
            if (empty($userInfo)) {
                list($code, $msg) = explode('|', config('response.msg9'));
                return apiError($msg, $code);
            }
            // 统计我的粉丝数量
            $followModel = model(FollowRelationModel::class);
            $query = $followModel->alias('fr');
            if (version_compare($this->request->header('version'), '3.6.3') >= 0) {
                $query->join('user u', 'fr.to_user_id = u.id')
                    ->where([
                        ['fr.from_user_id', '=', $userId],
                        ['fr.rel_type', '=', 1],
                        ['fr.to_user_id', '>', 0],
                        ['fr.to_shop_id', '=', 0],
                        ['u.account_status', '=', 1]
                    ]);
            } else {
                $query->where([
                    ['fr.from_user_id', '=', $userId],
                    ['fr.rel_type', '=', 1]
                ]);
            }
            $followCount = $query->count();
            // 统计我的粉丝数量
            $fansCount = $followModel->alias('fr')
                ->where([
                    'to_user_id' => $userId,
                    'rel_type' => 1
                ])
                ->count();
            // 获取授权店铺
            $userHasShopModel = model(UserHasShopModel::class);
            $authorizedShop = $userHasShopModel->getAuthorizedShop(['type' => 2, 'userId' => $userId]);
            // 返回消息
            $data = [
                'user_info' => [
                    'user_id' => $userId,
                    'nickname' => $userInfo['nickname'],
                    'avatar' => getImgWithDomain($userInfo['avatar']),
                    'thumb_avatar' => getImgWithDomain($userInfo['thumb_avatar']),
                ],
                'follow_count' => $followCount,
                'fans_count' => $fansCount,
                'has_shop' => empty($authorizedShop) ? 0 : 1
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '用户中心接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 个人主题申请&&申请个人文章.
     * 
     * @return \think\response\Json
     */
    public function applyTheme()
    {
        try {
            // 获取参数并校验
            $params = input('post.');
            $validate = validate(UserValidate::class);
            $result = $validate->scene('applyTheme')->check($params);
            if (!$result) {
                return apiError($validate->getError());
            }
            $cooperationType = input('cooperation_type/d', 1); // 合作类型，1:申请个人主题，2:申请个人文章
            if (!in_array($cooperationType, [1, 2])) {
                return apiError('合作类型错误');
            }

            // 新增个人主题申请记录
            $createData = [
                'user_id' => $this->getUserId(),
                'name' => $params['name'],
                'phone' => $params['phone'],
                'cooperation_type' => $cooperationType,
                'cooperation_intention' => $params['cooperation_intention'],
                'generate_time' => date('Y-m-d H:i:s')
            ];
            $model = model(UserThemeApplyModel::class);
            $record = $model::create($createData);
            // 响应
            return $record->id ? apiSuccess() : apiError();
        } catch (\Exception $e) {
            generateApiLog("申请个人主题接口异常信息：{$e->getMessage()}");
            return apiError(config('response.msg5'));
        }
    }
}
