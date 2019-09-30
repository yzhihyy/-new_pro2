<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/23
 * Time: 17:08
 */

namespace app\common\command\tasks;


use app\api\logic\v3_7_0\user\LiveShowLogic;
use app\api\model\v3_7_0\LiveShowModel;
use app\api\model\v3_7_0\UserModel;
use app\common\utils\date\DateHelper;
use app\common\utils\jPush\JpushHelper;
use think\Db;

trait LiveShowCheck
{
    /**
     * 视频聊天超时处理
     */
    public function liveShowHandle()
    {
        try{
            $list = $this->liveShowList();
            //需要循环遍历
            foreach($list as $key => $value){
                foreach($value as $v){
                    $nowTimestamp = DateHelper::getNowTimestamp();
                    if($v['status'] == 1){//已接通
                        $requestTime = Db::name('live_show_request')
                            ->where('live_show_id', $v['id'])
                            ->order('id', 'desc')
                            ->value('generate_time');

                        $requestTime = DateHelper::getNowTimestamp($requestTime);
                        //最后请求时间跟当前时间差2分钟就自动结束并推送
                        if(($nowTimestamp - $requestTime) >= 120){
                            Db::startTrans();
                            $update = [
                                'id' => $v['id'],
                                'status' => 8,//后端结束
                                'end_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s'),
                            ];
                            //状态修改
                            $liveShowUpdate = Db::name('live_show')->update($update);
                            if($liveShowUpdate <= 0){
                                Db::rollback();
                                continue ;
                            }
                            //明细增加
                            $userOrderTransAdd = LiveShowLogic::getInstance()->userOrderTransAdd($v['id']);
                            if($userOrderTransAdd !== true){
                                Db::rollback();
                                continue ;
                            }
                            //余额增加
                            $userBalanceAdd = LiveShowLogic::getInstance()->userBalanceAdd($v['id']);
                            if(!$userBalanceAdd){
                                Db::rollback();
                                continue ;
                            }
                            //接通率修改
                            $rate = LiveShowLogic::getInstance()->calcCallCompletingRate(['live_show_id' => $v['id']]);
                            if($rate !== true){
                                Db::rollback();
                                continue ;
                            }
                            Db::commit();
                            $this->pushMsgToUser([$v['send_user_id'], $v['anchor_user_id']], $v['id']);
                        }
                    }

                    if($v['status'] == 4){//未接通
                        $waitTime = model(LiveShowModel::class)->where('id', $v['id'])->value('wait_time');
                        if(empty($waitTime)){
                            continue;
                        }
                        $waitTime = DateHelper::getNowTimestamp($waitTime);
                        if( ($nowTimestamp - $waitTime) >= 120){
                            Db::startTrans();
                            $update = [
                                'id' => $v['id'],
                                'status' => 8,//后端结束
                                'end_time' => DateHelper::getNowDateTime()->format('Y-m-d H:i:s'),
                            ];
                            //状态修改
                            $liveShowUpdate = Db::name('live_show')->update($update);
                            if($liveShowUpdate <= 0){
                                Db::rollback();
                                continue ;
                            }
                            //接通率修改
                            $rate = LiveShowLogic::getInstance()->calcCallCompletingRate(['live_show_id' => $v['id']]);
                            if($rate !== true){
                                Db::rollback();
                                continue ;
                            }
                            Db::commit();
                            $this->pushMsgToUser([$v['send_user_id'], $v['anchor_user_id']], $v['id']);
                        }
                    }
                }
            }
            model(LiveShowModel::class)->getConnection()->close();

        }catch (\Exception $e){
            static::generateLog('每分钟处理超时视频聊天接口错误：'.$e->getMessage().'文件：'.$e->getFile().'行号：'.$e->getLine());
        }

    }


    /**
     * 视频列表
     * @return \Generator
     * @throws
     */
    private function liveShowList()
    {
        $page = 0;
        $model =  model(LiveShowModel::class);
        while(true) {
            $where = [
                'status' => [1, 4],
            ];
            $list = $model->field(['id', 'send_user_id', 'anchor_user_id', 'status'])->where($where)->order('id', 'desc')->limit($page * 10, 10)->select();//每次取10条
            if(empty($list[0])){
                break;
            }

            $page++;

            yield $list;
        }
    }

    /**
     * 视频直播结束推送
     * @param array $userIds
     * @param $liveShowId
     */
    public function pushMsgToUser($userIds = [], $liveShowId)
    {
        if(!empty($userIds)){
            $registrationId = model(UserModel::class)->where('id', 'in', $userIds)->column('registration_id');
            JpushHelper::push($registrationId, [
                'title' => '视频已结束',
                'message' => '视频已结束',
                'extras' => [
                    'push_type' => 'finish_live_show',
                    'live_show_id' => (int)$liveShowId,
                ]
            ]);
        }
    }
}