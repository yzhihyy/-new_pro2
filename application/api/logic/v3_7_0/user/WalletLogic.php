<?php

namespace app\api\logic\v3_7_0\user;

use app\api\logic\BaseLogic;
use app\common\utils\date\DateHelper;

class WalletLogic extends BaseLogic
{
    /**
     * 处理明细记录
     *
     * @param array $transactions
     *
     * @return array
     */
    public function transactionsHandle(array $transactions)
    {
        $result = [];
        // 明细类型说明
        $typeDescArray = [
            4 => '提现',
            5 => '投票赠红包',
            8 => '视频收益'
        ];
        // 当前年份
        $currentYear = date('Y');
        // 银行和第三方支付图标
        $paymentIconArray = config('parameters.payment_icon');

        foreach ($transactions as $item) {
            $item['generateTime'] = DateHelper::getNowDateTime($item['generateTime']);
            // 默认显示标题
            $txTitle = config('app.app_name');
            // 默认显示图标
            $txIcon = config('app.app_logo');
            // 明细类型
            $txType = $item['type'];
            // 明细状态描述
            $statusDesc = '';
            // 视频时长
            $duration = 0;
            // 提现
            if ($txType == 4) {
                $txTitle = '微信提现';
                $txIcon = $paymentIconArray['WECHAT'];
                $statusDesc = $item['status'] == 1 ? '审核中' : ($item['status'] == 2 ? '失败' : '成功');
            }
            // 投票赠红包
            elseif ($txType == 5 && $item['shopId']) {
                $txTitle = $item['shopName'];
                $txIcon = $item['shopThumbImage'];
            } elseif ($txType == 8 && $item['sendUserId']) {    // 视频收益
                $txTitle = $item['sendUserNickname'];
                $txIcon = $item['sendUserThumbAvatar'];
                $duration = ceil((strtotime($item['end_time']) - strtotime($item['start_time']))/60);
            }

            $dateFormat = 'm月d日 H:i';
            if ($item['generateTime']->format('Y') != $currentYear) {
                $dateFormat = "Y年{$dateFormat}";
            }

            $symbol = $this->isInflowTransaction($txType) ? '+' : ($this->isOutflowTransaction($txType, $item['status']) ? '-' : '');
            $result[] = [
                'tx_id' => $item['transactionId'],
                'tx_title' => $txTitle,
                'tx_icon' => getImgWithDomain($txIcon),
                'tx_time' => $item['generateTime']->format($dateFormat),
                'tx_type' => $item['type'],
                'tx_type_desc' => $typeDescArray[$txType] . $statusDesc,
                'tx_flag' => $symbol == '+' ? 1 : ($symbol == '-' ? 2 : 3),
                'tx_amount' => $symbol . $item['amount'],
                'duration' => $duration,
            ];
        }

        return $result;
    }

    /**
     * 是否是收入明细
     *
     * @param int $type 明细记录类型
     * 5:投票赠红包
     *
     * @return bool
     */
    public function isInflowTransaction($type)
    {
        return in_array($type, [5, 8]);
    }

    /**
     * 是否是支出明细
     *
     * @param int $type 明细记录类型 4:用户提现
     * @param int $status 状态 1：审核中, 2：审核失败, 3：审核成功或完成
     *
     * @return bool
     */
    public function isOutflowTransaction($type, $status)
    {
        // 如果是提现失败则不算支出
        return in_array($type, [4]) && $status != 2;
    }
}