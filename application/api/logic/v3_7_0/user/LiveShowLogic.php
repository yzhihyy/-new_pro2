<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/30
 * Time: 15:39
 */

namespace app\api\logic\v3_7_0\user;

use app\api\logic\AbstractLogic;
use app\api\model\v3_0_0\OrderModel;
use app\api\model\v3_0_0\UserTransactionsModel;
use app\api\model\v3_5_0\FollowRelationModel;
use app\api\model\v3_7_0\AnchorUserModel;
use app\api\model\v3_7_0\LiveShowModel;
use app\api\model\v3_7_0\LiveShowRequestModel;
use app\api\model\v3_7_0\UserModel;
use app\api\service\PushKitService;
use app\common\utils\date\DateHelper;
use app\common\utils\string\StringHelper;
use think\Db;

class LiveShowLogic extends AbstractLogic
{
    private static $_instance;
    private function __construct(){}
    private function __clone() {}

    public static function getInstance()
    {
        if(!(static::$_instance instanceof static)) {
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    /**
     * 视频操作
     * @param array $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function liveShowAction(array $params): array
    {
        Db::startTrans();
        switch ($params['action_type']){
            case 0://发起视频
                $result = $this->liveShowSend($params);
                break;
            case 1://接收视频
                $result = $this->liveShowReceive($params);
                break;
            case 2:
                $result = $this->liveShowRefuse($params);
                break;
            case 3:
                $result = $this->liveShowEnd($params);
                break;
            case 4:
                $result = $this->liveShowWait($params);
                break;
        }
        if(isset($result)){
            if(empty($result['msg'])){
                if(in_array($params['action_type'], [1, 2])){
                    $rate = $this->calcCallCompletingRate($params);
                    if($rate !== true){
                        return $this->logicResponse($rate);
                    }
                }
                Db::commit();
                return $this->logicResponse([], $result['data'] ?: (object)[]);
            }
        }
        Db::rollback();
        return $this->logicResponse($result['msg'] ?? '视频操作失败');

    }

    /**
     * 视频直播发送
     * @param $params
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function liveShowSend($params)
    {
        $anchorInfo = model(AnchorUserModel::class)->field(['meeting_price', 'user_id'])->find($params['anchor_id']);
        $sendUserId = $this->getUserId();
        if($sendUserId == $anchorInfo->user_id){
            return $this->logicResponse(config('response.msg112'));
        }
        $userModel = model(UserModel::class);
        $senderInfo = $userModel->field(['copper_coin', 'nickname'])->find($sendUserId);
        if($senderInfo->copper_coin < $anchorInfo->meeting_price){
            return $this->logicResponse(explode('|',config('response.msg110')));
        }
        $data = [
            'send_user_id' => $sendUserId,
            'anchor_id' => $params['anchor_id'],
            'anchor_user_id' => $anchorInfo->user_id,
            'meeting_price' => $anchorInfo->meeting_price,
            'status' => 0,//发送
            'generate_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s'),
        ];

        $insertId = Db::name('live_show')->insertGetId($data);
        if ($insertId) {
            $isFollow = $this->followRelationByShowId($sendUserId, $anchorInfo->user_id) ? 1 : 0;
            $userInfo = [
                'live_show_id' => $insertId,
                'meeting_price' => $anchorInfo->meeting_price,
                'meeting_desc' => '视频'.$anchorInfo->meeting_price.'铜板/分钟',
                'is_follow' => $isFollow,
            ];
            $pushKitDeviceToken = $userModel->where('id', $anchorInfo->user_id)->value('push_kit_device_token');
            PushKitService::push($pushKitDeviceToken, '', ['nickname' => $senderInfo->nickname]);
            return $this->logicResponse([], $userInfo);
        }
        return $this->logicResponse('视频发送失败');
    }

    /**
     * 视频直播接收
     * @param $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function liveShowReceive($params)
    {
        $liveShowModel = model(LiveShowModel::class);
        $liveInfo = $liveShowModel->whereIn('status', '1,2,3')->find($params['live_show_id']);
        if(!empty($liveInfo)){
            return $this->logicResponse('视频直播数据错误');
        }
        $data = [
            'id' => $params['live_show_id'],
            'status' => 1,//接收
            'start_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s')
        ];
        $update = Db::name('live_show')->update($data);
        if($update > 0){
            $live = $liveShowModel->field(['send_user_id', 'anchor_id', 'anchor_user_id'])->find($params['live_show_id']);
            $add = $this->addLiveShowRequest($params['live_show_id']);
            if($add !== true){//返回失败
                return $this->logicResponse($add);
            }
            $anchorUpdate = Db::name('anchor_user')->where('id', $live->anchor_id)->setInc('call_completing_count', 1);
            if(!$anchorUpdate){
                return $this->logicResponse('视频直播数据更新失败');
            }
            $isFollow = $this->followRelationByShowId($live->anchor_user_id, $live->send_user_id) ? 1 : 0;
            return $this->logicResponse([], ['is_follow' => $isFollow]);
        }
        return $this->logicResponse('视频接收失败');
    }


    /**
     * 视频拒绝
     * @param $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function liveShowRefuse($params)
    {
        $liveShowId = $params['live_show_id'];
        $liveShowModel = model(LiveShowModel::class);
        $liveInfo = $liveShowModel->whereIn('status', '1,2,3')->find($liveShowId);
        if(!empty($liveInfo)){
            return $this->logicResponse('视频直播数据错误');
        }
        $data = [
            'id' => $liveShowId,
            'status' => 2,//拒绝
            'end_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s'),
        ];

        $update = Db::name('live_show')->update($data);

        if($update > 0){
            return $this->logicResponse();
        }
        return $this->logicResponse('视频拒绝失败');
    }

    /**
     * 视频直播结束
     * @param $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function liveShowEnd($params)
    {
        $liveShowId = $params['live_show_id'];
        $liveShowModel = model(LiveShowModel::class);
        $liveInfo = $liveShowModel->whereIn('status', '2,3')->find($liveShowId);
        if(!empty($liveInfo)){
            return $this->logicResponse('视频直播数据错误');
        }
        $data = [
            'id' => $liveShowId,
            'status' => 3,//结束
            'end_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s'),
        ];

        $update = Db::name('live_show')->update($data);
        if($update > 0){
            $orderTransAdd = $this->userOrderTransAdd($liveShowId);//明细增加
            if($orderTransAdd !== true){
                return $this->logicResponse($orderTransAdd);
            }
            $balanceAdd = $this->userBalanceAdd($liveShowId);//余额增加
            if($balanceAdd !== true){
                return $this->logicResponse('余额操作失败');
            }
            return $this->logicResponse();
        }
        return $this->logicResponse('视频结束失败');
    }

    /**
     * 视频直播进入等待状态
     * @param $params
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function liveShowWait($params)
    {
        $liveShowId = $params['live_show_id'];
        $liveShowModel = model(LiveShowModel::class);
        $liveInfo = $liveShowModel->where('status', 4)->find($liveShowId);
        if(!empty($liveInfo)){
            return $this->logicResponse('视频直播数据错误');
        }
        $data = [
            'id' => $liveShowId,
            'status' => 4,//接通等待
            'wait_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s'),
        ];

        $update = Db::name('live_show')->update($data);
        if($update > 0) {
            return $this->logicResponse([]);
        }
        return $this->logicResponse('视频等待失败');
    }


    /**
     * 查询关注关系
     * @param $fromUserId
     * @param $toUserId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function followRelationByShowId($fromUserId, $toUserId)
    {
        return model(FollowRelationModel::class)
            ->where([
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'rel_type' => 1,
            ])
            ->find() ? true : false;
    }


    /**
     * 直播每分钟数据更新
     * @param $params
     * @return array
     * @throws \think\Exception
     */
    public function liveShowRequest($params)
    {
        //直播状态查询
        $liveInfo = model(LiveShowModel::class)->field(['send_user_id', 'anchor_user_id', 'meeting_price'])->where('status', 1)->find($params['live_show_id']);
        if(empty($liveInfo)){
            return $this->logicResponse('视频已结束');
        }
        //更新数据
        Db::startTrans();
        $showRequestAndCopper = $this->addLiveShowRequest($params['live_show_id']);
        if($showRequestAndCopper !== true){
            Db::rollback();
            return $this->logicResponse($showRequestAndCopper);
        }
        //更新用户铜板明细todo
        Db::commit();
        $userModel = model(UserModel::class);
        $sendUserCopperCoin = $userModel->where('id', $liveInfo->send_user_id)->value('copper_coin');
        return $this->logicResponse([], ['send_user_copper_coin' => $sendUserCopperCoin]);
    }


    /**
     * 添加主播余额
     * @param $liveShowId
     * @return bool
     * @throws \think\Exception
     */
    public function userBalanceAdd($liveShowId)
    {
        $anchorUserId = model(LiveShowModel::class)->where('id', $liveShowId)->value('anchor_user_id');
        $add = $this->userBalanceStatics($liveShowId);
        if($add > 0){
            $update = DB::name('user')->where('id', $anchorUserId)->setInc('balance', $add);
            if($update){
                return true;
            }
        }
        return false;
    }

    /**
     * 铜板转余额
     * @param $liveShowId
     * @return int
     */
    public function userBalanceStatics($liveShowId)
    {
        $anchorId = model(LiveShowModel::class)->where('id', $liveShowId)->value('anchor_id');
        $sum = model(LiveShowRequestModel::class)->where('live_show_id', $liveShowId)->sum('meeting_price');
        $discountPer = model(AnchorUserModel::class)->where('id', $anchorId)->value('discount_per');
        $add = number_format(((100 - $discountPer) / 100 * $sum / 10), 2);//10个铜板转换1块钱，保留2位小数点
        return $add;
    }

    /**
     * 订单和明细增加
     * @param $liveShowId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userOrderTransAdd($liveShowId)
    {
        $tranModel = model(UserTransactionsModel::class);
        $find = $tranModel->where(['order_id' => $liveShowId, 'type' => 8])->value('id');
        if(empty($find)){
            $liveShowInfo = model(LiveShowModel::class)->field('send_user_id, anchor_user_id')->find($liveShowId);
            $anchorUserId = $liveShowInfo->anchor_user_id;
            $amount = $this->userBalanceStatics($liveShowId);
            if($amount <= 0){
                return '直播金额错误';
            }
            $nowTime = DateHelper::getNowDateTime()->format('Y-m-d H:i:s');

            $beforeAmount = model(UserModel::class)->where('id', $anchorUserId)->value('balance');
            $transData = [
                'user_id' => $anchorUserId,
                'type' => 8,//主播收入
                'record_num' => StringHelper::generateNum('TX'),
                'order_id' => $liveShowId,
                'amount' => $amount,
                'before_amount' => $beforeAmount,
                'after_amount' => $beforeAmount + $amount,
                'payment_time' => $nowTime,
                'status' => 3,//审核完成
                'generate_time' => $nowTime,
            ];
            $transAdd = Db::name('user_transactions')->insertGetId($transData);//主播收入
            if(!$transAdd){
                return '交易订单生成失败';
            }
            $copperData = [
                'user_id' => $liveShowInfo->send_user_id,
                'live_show_id' => $liveShowId,
                'copper_type' => 4, //视频通话
                'copper_count' => model(LiveShowRequestModel::class)->where('live_show_id', $liveShowId)->sum('meeting_price'),
                'action_type' =>  2, //支出
                'generate_time' => $nowTime,
            ];
            $copperInsert = Db::name('user_copper_detail')->insertGetId($copperData);
            if(!$copperInsert){
                return '用户铜板明细生成失败';
            }
            return true;
        }
        return '视频订单已存在';
    }

    /**
     * 添加请求记录并扣除铜板
     * @param $liveShowId
     * @return bool|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addLiveShowRequest($liveShowId)
    {
        $liveModel = model(LiveShowModel::class);
        $userModel = model(UserModel::class);
        $info = $liveModel->field(['send_user_id', 'meeting_price'])->find($liveShowId);
        $copperCoin = $userModel->where('id', $info->send_user_id)->value('copper_coin');
        if($copperCoin < $info->meeting_price){
            return explode('|', config('response.msg110'));
        }
        $data = [
            'live_show_id' => $liveShowId,
            'meeting_price' => $info->meeting_price,
            'generate_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s'),
        ];
        //添加数据
        $showRequestAdd = Db::name('live_show_request')->insert($data);
        if(!$showRequestAdd){
            return '请求更新失败';
        }
        //更新用户铜板
        $updateSendUser = $userModel->where('id', $info->send_user_id)->setDec('copper_coin', $info->meeting_price);
        if(!$updateSendUser){
            return '铜板更新失败';
        }
        return true;
    }

    /**
     * 计算接通率
     * @param $params
     * @return int
     */
    public function calcCallCompletingRate($params)
    {
        $anchorModel = model(AnchorUserModel::class);
        $liveShowModel = model(LiveShowModel::class);
        $anchorId = $liveShowModel->where('id', $params['live_show_id'])->value('anchor_id');
        $liveShowCount = $liveShowModel->where(['anchor_id' => $anchorId, 'status' => [1,2,3,8]])->count();
        $totalCount = $anchorModel->where('id', $anchorId)->value('call_completing_count');
        if($totalCount == 0){
            return true;
        }
        $callCompletingRate = $anchorModel->where('id', $anchorId)->value('call_completing_rate');
        $per = round($totalCount / $liveShowCount, 3);
        if($per == $callCompletingRate){
            return true;
        }
        $update = Db::name('anchor_user')->where('id', $anchorId)->setField('call_completing_rate', $per);
        if($update > 0){
            return true;
        }
        return '接通数据更新失败';
    }

    public function addCopperDetail($params)
    {
        $liveShowModel = model(LiveShowModel::class);
        $requestModel = model(LiveShowRequestModel::class);
        $find = $requestModel->where('live_show_id', $params['live_show_id']);
    }
}