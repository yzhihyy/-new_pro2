<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/23
 * Time: 17:08
 */

namespace app\common\command\tasks;

use app\api\model\v3_0_0\SettingModel;
use app\api\model\v3_5_0\FollowRelationModel;

trait UserFollowAdd
{
    protected $followAddKey = 'fo:follow_user_add_list';



    //redis添加机器人随机数- 发布视频/随记
    public function userFollowHandle()
    {
        try{
            /** @var \Redis $redis */
            $redis = static::getRedis();
            $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
            $fansValue = model(SettingModel::class)->where(['status' => 1, 'group' => 'video_release', 'key' => 'play'])->value('value');
            while(true){
                //获取随记增加机器人数
                $randNum = static::getRandNum($fansValue);
                if($randNum > 0){
                    $data = $redis->blpop($this->followAddKey, 0);
                    $userId = $data[1];//获取用户id
                }

            }
        }catch (\Exception $e){
            static::generateLog('redis添加用户粉丝异常');
        }


    }

    //获取机器人列表
    protected function getLimitRobotList($limit)
    {

    }
}