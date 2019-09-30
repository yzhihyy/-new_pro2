<?php

namespace app\api\traits;

use app\common\utils\{
    sms\CaptchaHelper, string\StringHelper
};
use app\api\model\v2_0_0\{
    CaptchaModel, UserTransactionsModel, ShopNodeModel
};
use app\api\model\v3_0_0\SettingModel;

trait AbstractLogicTrait
{
    /**
     * 距离显示格式化
     *
     * @param $distance
     *
     * @return string
     */
    public function showDistance($distance)
    {
        $distance = (int)$distance;
        if ($distance < 0) {
            return '未知';
        }
        // 以整百显示
        $n = $distance + 100;
        $mod = $n % 100;
        $distance = $n - $mod;
        // 超过1000米显示km
        if ($distance >= 1000) {
            return $distance / 1000 . 'km';
        } else {
            return $distance . 'm';
        }
    }

    /**
     * 浏览量显示格式化
     *
     * @param $views
     *
     * @return string
     */
    public function showViews($views)
    {
        $views = (int)$views;
        // 超过10000次显示
        if ($views >= 10000) {
            // 以整千显示
            $mod = $views % 1000;
            $views = $views - $mod;
            return $views / 10000 . '万人浏览';
        } else {
            return $views . '人浏览';
        }
    }

    /**
     * 保存用户交易明细
     *
     * @param int $userId 用户 ID
     * @param string $amount 金额
     * @param array $extra 额外参数
     *
     * @return string
     */
    protected function saveUserTransactionsRecord($userId, $amount, $extra = [])
    {
        // 实例化用户交易明细模型
        /** @var UserTransactionsModel $userTransactionsModel */
        $userTransactionsModel = model(UserTransactionsModel::class);
        $recordData = array_merge([
            'record_num' => StringHelper::generateNum('TX'),
            'user_id' => $userId,
            'amount' => $amount,
            'generate_time' => date('Y-m-d H:i:s')
        ], $extra);
        // 添加明细记录
        $record = $userTransactionsModel->insertGetId($recordData);
        return $record;
    }

    /**
     * 按照免单次数为一个周期，计算已消费次数
     *
     * @param $countNormalOrder
     * @param $freeOrderFrequency
     *
     * @return int
     */
    public function getAlreadyBuyTimes($countNormalOrder, $freeOrderFrequency)
    {
        // 不设置免单的情况
        if ($freeOrderFrequency == 0) {
            return $countNormalOrder;
        }
        // 设置免单的情况
        $mod = $countNormalOrder % $freeOrderFrequency;
        return $mod == 0 ? $freeOrderFrequency : $mod;
    }

    /**
     * 计算再消费几次可免单
     *
     * @param $countNormalOrder
     * @param $freeOrderFrequency
     *
     * @return int
     */
    public function getAlsoNeedBuyTimes($countNormalOrder, $freeOrderFrequency)
    {
        // 不设置免单的情况
        if ($freeOrderFrequency == 0) {
            return 0;
        }
        // 设置免单的情况
        $mod = $countNormalOrder % $freeOrderFrequency;
        if ($mod == 0) {
            return 0;
        }
        return $freeOrderFrequency - $mod;
    }

    /**
     * 检测接口权限
     *
     * @param string $action
     * @param string $authorizedRule
     *
     * @return array
     *
     * @throws \Exception
     */
    public function detectInterfacePermissions(string $action, string $authorizedRule)
    {
        // -1代表拥有全部权限
        if ($authorizedRule !== '-1') {
            /** @var ShopNodeModel $shopNodeModel */
            $shopNodeModel = model(ShopNodeModel::class);
            // 节点列表
            $shopNodes = $shopNodeModel->getShopNodes(['isMenu' => 0]);
            $shopNodesArray = array_column($shopNodes, null, 'action');
            // 接口需验证权限
            if (array_key_exists($action, $shopNodesArray)) {
                // 接口对应的节点ID
                $nodeId = $shopNodesArray[$action]['nodeId'];
                // 店铺所分配给用户的权限
                $authorizedRuleArray = explode(',', $authorizedRule);
                if (!in_array($nodeId, $authorizedRuleArray)) {
                    list($code, $msg) = explode('|', config('response.msg59'));
                    return compact('msg', 'code');
                }
            }
        }

        return [];
    }

    /**
     * 校验手机短信验证码
     *
     * @param string $phone
     * @param string $code
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public function phoneCodeVerify(string $phone, string $code)
    {
        // 校验验证码是否正确
        /** @var CaptchaModel $captchaModel */
        $captchaModel = model(CaptchaModel::class);
        $captcha = $captchaModel->checkLoginCode([
            'phone' => $phone,
            'code' => $code
        ]);
        if (empty($captcha)) {
            return config('response.msg6');
        }
    }

    /**
     * 发送手机短信验证码
     *
     * @param array $data
     *
     * @return string|bool
     */
    public function sendCaptcha(array $data)
    {
        $captchaHelper = new CaptchaHelper();
        $result = $captchaHelper->sendAlibabaCaptcha($data);
        if (!$result) {
            return config('response.msg12');
        }

        // 短信发送失败
        if($result->children()->result->err_code != 0 || !empty($result->children()->code) ) {
            return sprintf('%s', $result->children()->sub_msg);
        }

        return true;
    }

    /**
     * 数量显示格式化，以万为单位.
     *
     * @param int $number
     *
     * @return string
     */
    public function numberFormat($number)
    {
        if ($number >= 10000) {
            $w = (int)($number / 10000);
            $k = (int)(($number % 10000) / 1000);
            if ($k >= 1) {
                return $w . '.' . $k . 'w';
            } else {
                return $w . 'w';
            }
        } else {
            return (string) $number;
        }
    }

    /**
     * 获取系统配置
     *
     * @param string $group
     *
     * @return array
     * @throws \Exception
     */
    public function getSettingsByGroup(string $group)
    {
        /** @var SettingModel $settingModel */
        $settingModel = model(SettingModel::class);
        $settings = $settingModel->where(['group' => $group, 'status' => 1])->select()->toArray();
        $settings = array_column($settings, null, 'key');

        return $settings;
    }
}
