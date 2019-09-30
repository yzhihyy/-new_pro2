<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/28
 * Time: 16:22
 */

namespace app\api\controller\v3_7_0\user;


use app\api\logic\v3_7_0\user\AnchorLogic;
use app\api\logic\v3_7_0\user\BannerLogic;
use app\api\logic\v3_7_0\user\LiveShowLogic;
use app\api\logic\v3_7_0\user\UserCenterLogic;
use app\api\model\v3_0_0\UserModel;
use app\api\model\v3_7_0\LiveShowModel;
use app\api\model\v3_7_0\UserCoverModel;
use app\api\model\v3_7_0\UserTagModel;
use app\api\Presenter;
use app\api\validate\v3_7_0\AnchorUserValidate;
use app\api\validate\v3_7_0\UserValidate;
use app\common\utils\string\StringHelper;
use Exception;
use think\Db;

class User extends Presenter
{

    /**
     * 相亲首页banner
     * @return \think\response\Json
     */
    public function indexBannerInfo()
    {
        try{
            $bannerList = BannerLogic::getBannerList(['position' => 3]);//相亲首页
            $info = [
                'banner_list' => $bannerList
            ];
            return apiSuccess($info);

        }catch (\Exception $e){
            generateApiLog('相亲首页banner异常'. $e->getMessage());
            return apiError();
        }
    }
    /**
     * 相亲首页.
     *
     * @return \think\response\Json
     */
    public function meetingIndex()
    {
        try{
            $params = $this->request->get();
            $result = $this->validate($params, AnchorUserValidate::class.'.AnchorUserList');
            if ($result !== true) {
                return apiError($result);
            }
            $params['limit'] = config('parameters.page_size_level_2');
            $params['page'] = $params['page'] ?? 0;

            $anchorList = AnchorLogic::getAnchorList($params);

            $info = [];
            $info['anchor_list'] = $anchorList;

            return apiSuccess($info);

        }catch (\Exception $e){
            $logContent = '相亲首页接口异常：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError();
        }
    }


    /**
     * 发起视频
     * @return \think\response\Json
     */
    public function liveShow()
    {
        try{
            $params = $this->request->post();
            $result = $this->validate($params, AnchorUserValidate::class.'.LiveShow');
            if ($result !== true) {
                return apiError($result);
            }
            $userInfo = LiveShowLogic::getInstance()->liveShowAction($params);
            if(!empty($userInfo['msg'])){
                if (is_array($userInfo['msg'])) {
                    return apiError(end($userInfo['msg']), reset($userInfo['msg']));
                }
                return apiError($userInfo['msg']);
            }
            $info = ['info' => $userInfo['data']];
            return apiSuccess($info);

        }catch (\Exception $e){
            generateApiLog('视频直播接口异常：'.$e->getMessage().'文件：'.$e->getFile().'行号：'.$e->getLine());
            return apiError();
        }
    }


    /**
     * 每分钟直播请求
     * @return \think\response\Json
     */
    public function liveShowMinRequest()
    {
        try{
            $params = $this->request->post();
            $result = $this->validate($params, AnchorUserValidate::class.'.MinMeetingPay');
            if ($result !== true) {
                return apiError($result);
            }
            $done = LiveShowLogic::getInstance()->liveShowRequest($params);
            if(!empty($done['msg'])){
                if (is_array($done['msg'])) {
                    return apiError(end($done['msg']), reset($done['msg']));
                }
                return apiError($done['msg']);
            }
            return apiSuccess(['info' => $done['data']]);

        }catch (\Exception $e){
            generateApiLog('每分钟直播请求接口异常：'.$e->getMessage());
            return apiError();
        }
    }
    /**
     * 用户主页.
     *
     * @return \think\response\Json
     */
    public function home()
    {
        try {
            // 获取用户并判断是否存在
            $userId = input('user_id/d', 0);
            if ($userId <= 0) {
                return apiError('用户不存在');
            }
            $userModel = new UserModel();
            $user = $userModel->find($userId);
            if (empty($user)) {
                return apiError('用户不存在');
            }
            // 获取用户主页信息
            $userCenterLogic = new UserCenterLogic();
            $where = [
                'userId' => $userId,
                'longitude' => input('longitude/f', null),
                'latitude' => input('latitude/f', null),
            ];
            $info = $userCenterLogic->getUserCenterInfo($where);
            $info = StringHelper::nullValueToEmptyValue($info);
            // 是否已经拉黑
            $isInBlackList = $userModel->isInBlackList([
                'type' => 1,
                'loginUserId' => $this->getUserId(),
                'userId' => $userId,
            ]);
            $info['in_black_list'] = $isInBlackList ? 1 : 0;
            // 响应
            return apiSuccess($info);
        } catch (Exception $e) {
            $logContent = '用户主页接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 修改个人资料.
     *
     * @return \think\response\Json
     */
    public function saveUserInfo()
    {
        try {
            // 判断用户是否存在
            $userId = $this->getUserId();
            $userModel = model(UserModel::class);
            $userInfo = $userModel->find($userId);
            if (empty($userInfo)) {
                return apiError(config('response.msg9'));
            }
            // 更新个人资料
            $updateData = [];
            // 头像
            $avatar = input('avatar', null);
            $thumb_avatar = input('thumb_avatar', null);
            if (!empty($avatar) && !empty($thumb_avatar)) {
                $domain = config('resources_domain');
                $updateData['avatar'] = str_replace($domain, '', $avatar);
                $updateData['thumb_avatar'] = str_replace($domain, '', $thumb_avatar);
            }

            // 封面
            $coverList = input('cover_list/s', null);
            if ($coverList !== null) {
                $coverList = explode(',', $coverList);
                $coverList = array_filter($coverList);
                $userCoverModel = new UserCoverModel();
                if (!empty($coverList)) {
                    // 清空封面
                    $userCoverModel->where('user_id', '=', $userId)->delete();
                    // 新增封面
                    $data = [];
                    foreach ($coverList as $cover) {
                        $item = [];
                        $item['user_id'] = $userId;
                        $item['cover'] = $cover;
                        // 缩略图
                        $arr = explode('/', $cover);
                        $fileName = end($arr);
                        $thumb_cover = str_replace($fileName, 'thumb_' . $fileName, $cover);
                        $item['thumb_cover'] = $thumb_cover;
                        $item['generate_time'] = date('Y-m-d H:i:s');
                        $data[] = $item;
                    }
                    $userCoverModel->saveAll($data);
                } else {
                    // 清空封面
                    $userCoverModel->where('user_id', '=', $userId)->delete();
                }
            }

            // 昵称
            $nickname = input('nickname', null);
            if (!empty($nickname)) {
                $updateData['nickname'] = $nickname;
            }

            // 兴趣爱好
            $hobbyList = input('hobby_list/s', null);
            if ($hobbyList !== null) {
                $hobbyList = explode(',', $hobbyList);
                $userTagModel = new UserTagModel();
                if (!empty($hobbyList)) {
                    // 清空
                    $userTagModel->where([
                        ['user_id', '=', $userId],
                        ['tag_type', '=', 2]
                    ])->delete();
                    // 设置兴趣爱好
                    $data = [];
                    foreach ($hobbyList as $tag_id) {
                        $item = [];
                        $item['user_id'] = $userId;
                        $item['tag_type'] = 2;
                        $item['tag_id'] = $tag_id;
                        $item['generate_time'] = date('Y-m-d H:i:s');
                        $data[] = $item;
                    }
                    $userTagModel->saveAll($data);
                } else {
                    // 清空
                    $userTagModel->where([
                        ['user_id', '=', $userId],
                        ['tag_type', '=', 2]
                    ])->delete();
                }
            }

            // 个性标签
            $personalityLabelList = input('personality_label_list/s', null);
            if ($personalityLabelList !== null) {
                $personalityLabelList = explode(',', $personalityLabelList);
                $userTagModel = new UserTagModel();
                if (!empty($personalityLabelList)) {
                    // 清空
                    $userTagModel->where([
                        ['user_id', '=', $userId],
                        ['tag_type', '=', 3]
                    ])->delete();
                    // 设置兴趣爱好
                    $data = [];
                    foreach ($personalityLabelList as $tag_id) {
                        $item = [];
                        $item['user_id'] = $userId;
                        $item['tag_type'] = 3;
                        $item['tag_id'] = $tag_id;
                        $item['generate_time'] = date('Y-m-d H:i:s');
                        $data[] = $item;
                    }
                    $userTagModel->saveAll($data);
                } else {
                    // 清空
                    $userTagModel->where([
                        ['user_id', '=', $userId],
                        ['tag_type', '=', 3]
                    ])->delete();
                }
            }

            // 自我介绍
            $introduction = input('introduction/s', null);
            if ($introduction !== null) {
                if (!empty($introduction)) {
                    $updateData['introduction'] = $introduction;
                } else {
                    $updateData['introduction'] = '';
                }
            }

            // 年龄
            $age = input('age/d', null);
            if ($age !== null) {
                if (!empty($age)) {
                    $updateData['age'] = $age;
                } else {
                    $updateData['age'] = 0;
                }
            }

            // 星座
            $constellation = input('constellation/s', null);
            if ($constellation !== null) {
                if (!empty($constellation)) {
                    $updateData['constellation'] = $constellation;
                } else {
                    $updateData['constellation'] = '';
                }
            }

            // 身高
            $height = input('height/d', null);
            if ($height !== null) {
                if (!empty($height)) {
                    $updateData['height'] = $height;
                } else {
                    $updateData['height'] = 0;
                }
            }

            // 体重
            $weight = input('weight/f', null);
            if ($weight !== null) {
                if (!empty($weight)) {
                    $updateData['weight'] = $weight;
                } else {
                    $updateData['weight'] = 0;
                }
            }

            // 故乡
            $hometown = input('hometown/s', null);
            if ($hometown !== null) {
                if (!empty($hometown)) {
                    $updateData['hometown'] = $hometown;
                } else {
                    $updateData['hometown'] = '';
                }
            }

            // 更新信息
            $where = [
                'id' => $userId
            ];
            if (!empty($updateData)) {
                $result = $userInfo->force(true)
                    ->save($updateData, $where);
                if (!$result) {
                    return apiError(config('response.msg11'));
                }
            }
            return apiSuccess();
        } catch (Exception $e) {
            $logContent = '修改个人资料接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }


    /**
     * 铜板校验
     * @return \think\response\Json
     */
    public function copperCoinVerify()
    {
        try{
            $liveShowId = $this->request->post('live_show_id');
            if(empty($liveShowId)){
                return apiError('参数错误');
            }
            $copperCoin = $this->request->user->copper_coin;
            $errorResponse = explode('|', config('response.msg110'));
            if($copperCoin <= 0){
                return apiError(end($errorResponse), reset($errorResponse));
            }
            $meetingPrice = model(LiveShowModel::class)->where('id', $liveShowId)->value('meeting_price');
            if($copperCoin < $meetingPrice){
                return apiError(end($errorResponse), reset($errorResponse));
            }
            return apiSuccess();

        }catch (\Exception $e){
            $logContent = '铜板校验接口失败：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError();
        }

    }


    /**
     * 绑定邀请码
     * @return \think\response\Json
     */
    public function setMyInviteCode()
    {
        try{
            $params = $this->request->post();
            $result = $this->validate($params, UserValidate::class.'.InviteCode');
            if ($result !== true) {
                return apiError($result);
            }
            $userInfo = $this->request->user;
            if(!empty($userInfo['bind_invite_code'])){
                return apiError('邀请码已绑定，请不要重复操作');
            }
            if(!empty($userInfo['invite_code']) && ($userInfo['invite_code'] == $params['invite_code'])){
                return apiError('不能绑定自己的邀请码');
            }
            $data = [
                'id' => $userInfo['id'],
                'bind_invite_code' => $params['invite_code'],
            ];
            $update = Db::name('user')->update($data);
            if($update > 0){
                return apiSuccess();
            }
        }catch (\Exception $e){
            generateApiLog('绑定邀请码失败：'.$e->getMessage());
        }
        return apiError();
    }

    /**
     * 绑定iosPushKitDeviceToken
     * @return \think\response\Json
     */
    public function bindPushKitDeviceToken()
    {
        try{
            $pushKit = $this->request->post('push_kit_device_token');
            $userInfo = $this->request->user;
            if(empty($pushKit)){
                return apiError('参数错误');
            }
            $data = [
                'id' => $userInfo['id'],
                'push_kit_device_token' => $pushKit,
            ];
            $update = Db::name('user')->update($data);
            if($update > 0){
                return apiSuccess();
            }

        }catch (\Exception $e){
            generateApiLog('绑定pushKit失败：'.$e->getMessage());
        }
        return apiError();
    }
}