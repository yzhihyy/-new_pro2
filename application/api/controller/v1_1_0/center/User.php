<?php

namespace app\api\controller\v1_1_0\center;

use app\api\Presenter;
use app\common\utils\string\StringHelper;

class User extends Presenter
{
    /**
     * 免单卡列表
     * @return json
     */
    public function freeCardList()
    {
        try {
            // 用户id
            $userId = $this->getUserId();
            // 获取请求参数
            $paramsArray = input();
            // 免单规则模型
            $freeRuleModel = model('api/v1_1_0/FreeRule');
            $condition = [
                'userId' => $userId,
                'page' => isset($paramsArray['page']) ? $paramsArray['page'] : 0,
                'limit' => config('parameters.page_size_level_1')
            ];
            // 查询免单规则列表
            $freeRule = $freeRuleModel->getFreeRuleList($condition);
            $freeCardList = [];
            if (!empty($freeRule)) {
                foreach ($freeRule as $value) {
                    $info = [
                        'free_rule_id' => $value['freeRuleId'],
                        'shop_id' => $value['shop_id'],
                        'shop_name' => $value['shop_name'],
                        'shop_thumb_image' => getImgWithDomain($value['shop_thumb_image']),
                        'predict_free_money' => $value['order_count'] == 0 ? '0' : decimalDiv($value['consume_amount'], $value['order_count']),
                        'free_rule_status' => $value['status'],
                    ];
                    $freeCardList[] = $info;
                }
            }
            $data = [
                'free_card_list' => $freeCardList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '免单卡列表接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 免单卡详情
     * @return json
     */
    public function freeCardDetail()
    {
        try {
            // 获取请求参数
            $paramsArray = input();
            // 实例化验证器
            $validate = validate('api/v1_1_0/User');
            // 验证请求参数
            $checkResult = $validate->scene('freeCardDetail')->check($paramsArray);
            if (!$checkResult) {
                // 验证失败提示
                return apiError($validate->getError());
            }
            // 免单规则id
            $freeRuleId = $paramsArray['free_rule_id'];
            // 用户id
            $userId = $this->getUserId();
            // 免单规则模型
            $freeRuleModel = model('api/v1_1_0/FreeRule');
            $condition = [
                'userId' => $userId,
                'freeRuleId' => $freeRuleId,
            ];
            // 查询免单规则详情
            $freeRule = $freeRuleModel->getFreeRuleDetail($condition);
            if (empty($freeRule)) {
                return apiError(config('response.msg46'));
            }
            // 订单模型
            $orderModel = model('api/v1_1_0/Order');
            // 获取一轮免单的订单记录
            $order = $orderModel->getFreeRuleOrderList($condition);
            $orderList = [];
            if (!empty($order)) {
                foreach ($order as $value) {
                    $info = [
                        'pay_money' => $value['payment_amount'],
                        'pay_time' => $value['payment_time'],
                    ];
                    $orderList[] = $info;
                }
            }
            $data = [
                'shop_id' => $freeRule['shop_id'],
                'shop_name' => $freeRule['shop_name'],
                'shop_thumb_image' => getImgWithDomain($freeRule['shop_thumb_image']),
                'shop_free_order_frequency' => $freeRule['shop_free_order_frequency'],
                'order_count' => $freeRule['order_count'],
                'predict_free_money' => $freeRule['order_count'] == 0 ? '0' : decimalDiv($freeRule['consume_amount'], $freeRule['order_count']),
                'free_rule_status' => $freeRule['status'],
                'order_list' => $orderList
            ];
            $data = StringHelper::nullValueToEmptyValue($data);
            return apiSuccess($data);
        } catch (\Exception $e) {
            $logContent = '免单卡详情接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }
}
