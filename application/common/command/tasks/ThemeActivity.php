<?php

namespace app\common\command\tasks;

use app\api\model\v3_0_0\ThemeActivityModel;
use app\api\model\v3_0_0\ThemeActivityShopModel;
use app\common\utils\jPush\JpushHelper;
use app\common\utils\sms\CaptchaHelper;

trait ThemeActivity
{
    protected $themeActivityGoingKey = 'fo:theme_activity:going:';
    protected $themeActivityEndKey = 'fo:theme_activity:end';

    /**
     * 主题活动初始化
     *
     * @throws \Exception
     */
    public function themeActivityInit()
    {
        try {
            /** @var ThemeActivityModel $themeActivityModel */
            $themeActivityModel = model(ThemeActivityModel::class);
            // 获取进行中的主题活动
            $themeActivityList = $themeActivityModel->list([
                'theme_status' => [2]
            ]);

            if (!empty($themeActivityList)) {
                /** @var \Redis $redis */
                $redis = static::getRedis();
                $time = time();
                // 已结束的活动
                $finishedActivity = [];

                foreach ($themeActivityList as $themeActivity) {
                    // 主题活动ID
                    $themeActivityId = $themeActivity['theme_id'];
                    // 计算剩余多少秒活动结束
                    $surplusTime = strtotime($themeActivity['end_time']) - $time;
                    if ($surplusTime > 0) {
                        // 保存主题活动ID至Redis并设置过期时间
                        $redis->setex($this->themeActivityGoingKey . $themeActivityId, $surplusTime, $themeActivityId);
                    } else {
                        $finishedActivity[] = $themeActivityId;
                    }
                }

                // 更新已结束的活动状态
                if (!empty($finishedActivity)) {
                    $themeActivityModel->whereIn('id', $finishedActivity)->update([
                        'theme_status' => 3,
                    ]);
                }

                // 断开连接
                $themeActivityModel->getConnection()->close();
            }
        } catch (\Exception $e) {
            static::generateLog("主题活动初始化异常：{$e->getMessage()}");
        }
    }

    /**
     * 主题活动监听
     */
    public function themeActivityListen()
    {
        /** @var \Redis $redis */
        $redis = static::getRedis();
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        // 订阅过期事件
        $redis->psubscribe(['__keyevent@0__:expired'], function ($redis, $pattern, $chan, $msg) {
            $info = explode(':', $msg);
            /** @var \Redis $newRedis */
            $newRedis = static::getRedis();
            // 保存主题活动ID至队列
            $newRedis->rPush($this->themeActivityEndKey, end($info));
        });
    }

    /**
     * 主题活动处理
     */
    public function themeActivityHandle()
    {
        /** @var \Redis $redis */
        $redis = static::getRedis();
        $redis->setOption(\Redis::OPT_READ_TIMEOUT, -1);
        while (1) {
            $info = $redis->blPop($this->themeActivityEndKey, 0);
            $themeActivityId = $info[1];

            try {
                /** @var ThemeActivityModel $themeActivityModel */
                $themeActivityModel = model(ThemeActivityModel::class);
                // 配置断线重连
                $connection = $themeActivityModel->getConnection();
                $connection->setConfig(['break_reconnect' => true]);

                // 获取主题活动信息
                $themeActivity = $themeActivityModel->where([
                    'id' => $themeActivityId,
                    'theme_status' => 2,
                    'delete_status' => 0
                ])->find();
                if (!empty($themeActivity)) {
                    // 更新主题活动为已结束
                    $result = $themeActivityModel->where([
                        'id' => $themeActivity->id,
                        'theme_status' => 2,
                    ])->update([
                        'theme_status' => 3,
                    ]);
                    if (!$result) {
                        throw new \Exception("主题活动ID={$themeActivityId}更新失败");
                    }

                    // 极光推送和短信通知
                    $this->jpushAndSendCaptcha($themeActivity->id, $themeActivity->theme_title);
                }
            } catch (\Exception $e) {
                static::generateLog("更新主题活动异常：{$e->getMessage()},主题活动ID={$themeActivityId}");
            }
        }
    }

    /**
     * 极光推送和短信通知
     *
     * @param int    $themeActivityId
     * @param string $themeTitle
     *
     * @throws \Exception
     */
    private function jpushAndSendCaptcha(int $themeActivityId, string $themeTitle)
    {
        /** @var ThemeActivityShopModel $themeActivityShopModel */
        $themeActivityShopModel = model(ThemeActivityShopModel::class);
        $themeActivityShopList = $themeActivityShopModel->getThemeActivityShopList([
            'themeId' => $themeActivityId
        ]);
        if (!empty($themeActivityShopList)) {
            $captchaHelper = new CaptchaHelper();
            $count = 1;
            foreach ($themeActivityShopList as $shop) {
                $ranking = num2Ch((string)$count);

                if (!empty($shop['registratioinId'])) {
                    $content = sprintf(config('response.msg99'), $themeTitle, $shop['voteCount'], $ranking);
                    JpushHelper::push([$shop['registratioinId']], [
                        'title' => $content,
                        'message' => $content,
                        'extras' => [
                            'push_type' => 'themeActivityType',
                            'theme_id' => $shop['themeId'],
                            'theme_type' => $shop['themeType']
                        ]
                    ]);
                }

                if (!empty($shop['phone'])) {
                    $data = [
                        'phone' => $shop['phone'],
                        'smsParam' => json_encode(['name' => $themeTitle, 'm' => (string)$shop['voteCount'], 'n' => $ranking]),
                        'smsTemplateCode' => config('aliyunCaptcha.themeActivityFinishedTemplateId')
                    ];
                    $captchaHelper->sendAlibabaCaptcha($data);
                }

                $count++;
            }
        }
    }
}
