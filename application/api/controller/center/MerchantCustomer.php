<?php

namespace app\api\controller\center;

use app\api\Presenter;
use app\common\utils\string\StringHelper;

class MerchantCustomer extends Presenter
{
    /**
     * 获取商家客户列表
     *
     * @return \think\response\Json
     */
    public function index()
    {
        /**
         * @var \app\api\model\Order $orderModel
         */
        try {
            $pageNo = input('page/d', 0);
            if ($pageNo < 0) {
                $pageNo = 0;
            }
            $limit = config('parameters.page_size_level_2');
            $sort = input('sort/d', 1); // 排序默认按最新
            $userId = $this->getUserId();
            // 查询条件
            $where = [
                'page' => $pageNo,
                'limit' => $limit,
                'sort' => $sort,
                'userId' => $userId
            ];
            $orderModel = model('api/Order');
            $merchantCustomerList = $orderModel->getMerchantCustomerList($where);
            // 返回数据
            $dataList = [];
            foreach ($merchantCustomerList as $customer) {
                $info = [];
                $info['user_id'] = $customer['buyerId'];
                $info['user_nickname'] = $customer['buyerNickname'];
                $info['user_avatar'] = getImgWithDomain($customer['buyerAvatar']);
                $info['user_thumb_avatar'] = getImgWithDomain($customer['buyerThumbAvatar']);
                $info['consume_amount'] = $this->decimalFormat($customer['countMoney']); // 消费总额
                $info['consume_order'] = $this->decimalFormat($customer['countOrder'], true); // 消费次数
                $info['avg_money'] = $this->decimalFormat($customer['avgMoney']); // 客均价
                $info['last_pay_money'] = $this->decimalFormat($customer['lastPayMoney']); // 最近消费金额
                $info['last_pay_time'] = dateFormat($customer['lastPayTime']); // 最近消费时间
                array_push($dataList, $info);
            }
            $dataList = StringHelper::nullValueToEmptyValue($dataList);
            return apiSuccess([
                'customer_list' => $dataList
            ]);
        } catch (\Exception $e) {
            $logContent = '商家客户列表接口异常信息：' . $e->getMessage();
            generateApiLog($logContent);
            return apiError(config('response.msg5'));
        }
    }
}
