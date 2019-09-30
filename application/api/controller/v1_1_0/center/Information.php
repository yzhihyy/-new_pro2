<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/11
 * Time: 17:57
 */

namespace app\api\controller\v1_1_0\center;

use app\api\logic\v1_1_0\shop\DealInfo;
use app\api\Presenter;
use app\common\utils\date\DateHelper;
use app\common\utils\string\StringHelper;

class Information extends Presenter
{
    /**
     * 数据首页
     * @return \think\response\Json
     */
    public function statisIndex()
    {
        try{
            $userId = $this->getUserId();
            $model = model('api/v1_1_0/Shop');
            $info = $model->where(['user_id' => $userId])->field('id, shop_name')->find();
            //店铺信息
            $shopInfo = [
                'shop_name' => $info['shop_name'],
            ];
            $dealData = DealInfo::dealStatis($info['id']);
            //交易数据
            $data = [
                'shop_info' => $shopInfo,
                'deal_data' => $dealData,
            ];
            return apiSuccess($data);

        }catch (\Exception $e){

            $logContent = '数据首页接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();

    }

    /**
     * 客户统计
     * @return \think\response\Json
     */
 /*   public function statisConsumeList()
    {
        try{
            $userId = $this->getUserId();
            $model = model('api/v1_1_0/Shop');
            $info = $model->where(['user_id' => $userId])->field('id')->find();
            if(empty($info)){
                return apiError(config('response.msg10'));
            }

            $page = $this->request->get('page', 0);
            //客户统计
            $where = [
                'shop_id' => $info['id'],
                'page' => $page,
                'limit' => config('parameters.page_size_level_2'),
            ];
            $list = model('api/Order')->customerStatis($where);
            $consumeList = [];
            if(!empty($list)){
                foreach($list as $key => $value){
                    $paymentTime = dateFormat($value['paymentTime']);
                    $desc = $paymentTime.'在本店消费 ¥'.$value['paymentAmount'];
                    $arr = [];
                    $arr['avatar'] = getImgWithDomain($value['avatar']);
                    $arr['nickname'] = $value['nickname'];
                    $arr['user_id'] = $value['user_id'];
                    $arr['desc'] = $desc;
                    $consumeList[] = $arr;
                }
            }

            $data = [
                'consume_list' => $consumeList,
            ];
            return apiSuccess($data);

        }catch (\Exception $e){
            $logContent = '客户统计接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }*/

    /**
     * 全部订单
     * @return \think\response\Json
     */
    public function statisAllOrder()
    {
        try{
            $userId = $this->getUserId();
            $model = model('api/v1_1_0/Shop');
            $info = $model->where(['user_id' => $userId])->field('id')->find();
            if(empty($info)){
                return apiError(config('response.msg10'));
            }

            $page = $this->request->get('page', 0);
            //客户统计
            $where = [
                'shop_id' => $info['id'],
                'order_status' => 1,
                'page' => $page,
                'limit' => config('parameters.page_size_level_2'),

            ];
            $list = model('api/Order')->recentOrderList($where);

            foreach($list as $key => &$value){
                $value['avatar'] = getImgWithDomain($value['avatar']);
                $value['payment_time'] = DateHelper::getNowDateTime($value['payment_time'])->format('Y-m-d H:i');
            }

            $data = [
                'list' => $list,
            ];
            return apiSuccess($data);

        }catch (\Exception $e){
            $logContent = '今日订单接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 店铺交易数据
     * @return \think\response\Json
     */
    public function statisShopData()
    {
        try{
            $userId = $this->getUserId();
            $model = model('api/v1_1_0/Shop');
            $find = $model->where(['user_id' => $userId])->field('id')->find();
            if(empty($find)){
                return apiError(config('response.msg10'));
            }
            $info = [];
            //总数据
            $params = [
                'shop_id' => $find['id'],
                'type' => 'total',
            ];
            $info['total'] = DealInfo::statisShopData($params);

            //近三天
            $params = [
                'shop_id' => $find['id'],
                'type' => 'threeDays',
                'start_time' => DateHelper::getNowDateTime('-3 days')->format('Y-m-d').' 00:00:00',
                'end_time'   => DateHelper::getNowDateTime('-1 days')->format('Y-m-d'). ' 23:59:59',
            ];

            $info['threeDays'] = DealInfo::statisShopData($params);

            //近七天
            $params = [
                'shop_id' => $find['id'],
                'type' => 'sevenDays',
                'start_time' => DateHelper::getNowDateTime('-7 days')->format('Y-m-d').' 00:00:00',
                'end_time'   => DateHelper::getNowDateTime('-1 days')->format('Y-m-d'). ' 23:59:59',
            ];
            $info['sevenDays'] = DealInfo::statisShopData($params);

            //近30天
            $params = [
                'shop_id' => $find['id'],
                'type' => 'thirdtyDays',
                'start_time' => DateHelper::getNowDateTime('-1 month')->format('Y-m-d').' 00:00:00',
                'end_time'   => DateHelper::getNowDateTime('-1 days')->format('Y-m-d'). ' 23:59:59',
            ];
            $info['thirdtyDays'] = DealInfo::statisShopData($params);

            $info = StringHelper::snakeCase($info);
            $data = [ 'info' => $info ];
            return apiSuccess($data);

        }catch (\Exception $e){
            $logContent = '店铺交易数据接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 客户详情消费数据
     * @return \think\response\Json
     */
    public function statisCustomerDetail()
    {
        try{
            $customerId = input('user_id', 0);
            if($customerId <= 0){
                return apiError(config('response.msg9'));
            }
            $shopId = model('api/Shop')->where(['user_id' => $this->getUserId()])->value('id');

            if(empty($shopId)){
                return apiError(config('response.msg10'));
            }
            $customerInfo = model('api/User')->find($customerId);
            if(empty($customerInfo)){
                return apiError(config('response.msg9'));
            }
            //头部信息
            $info = [];
            $info['titleInfo'] = [
                'phone' => $customerInfo['phone'],
                'nickname' => $customerInfo['nickname'],
                'thumb_avatar' => getImgWithDomain($customerInfo['thumb_avatar'])
            ];

            $field = [
                'max(id) as id',
                'sum(payment_amount) as totalMoney',
                'count(id) as totalCount',
                'SUM(payment_amount) / COUNT(id) as avgMoney',
            ];
            //消费数据
            $orderInfo = model('api/Order')->where(['user_id' => $customerId, 'shop_id' => $shopId, 'order_status' => 1])->field($field)->find()->toArray();
            $currInfo = model('api/Order')->field(['consume_amount', '(shop_free_order_frequency - current_number) as residueCount' ])->find($orderInfo['id'])->toArray();

            $rule = model('api/FreeRule')->where(['user_id' => $customerId, 'shop_id' => $shopId, 'status' => 1])->find();
            $orderInfo['predict_free_money'] = $rule['order_count'] == 0 ? '0.00' :
                $this->decimalFormat(decimalDiv($rule['consume_amount'], $rule['order_count']), true);//预计可免金额

            $orderInfo['avgMoney'] = $this->decimalFormat($orderInfo['avgMoney']);//客均价
            $orderInfo['totalMoney'] = $orderInfo['totalMoney'] ?: '0.00';//总交易额
            $orderInfo['consume_amount'] = $currInfo['consume_amount'] ?: '0.00';//该轮消费总额
            $orderInfo['residueCount'] = $currInfo['residueCount'] ? $currInfo['residueCount'] <=0 ? 0 : $currInfo['residueCount'] :0 ;//免单还需次数

            $info['consumInfo'] = $orderInfo;
            $info = StringHelper::snakeCase($info);
            $info = StringHelper::nullValueToEmptyValue($info);
            return apiSuccess($info);

        }catch (\Exception $e){
            $logContent = '客户详情消费数据接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }

    /**
     * 订单记录接口
     * @return \think\response\Json
     */
    public function statisOrderRecord()
    {
        try{
            $customerId = input('user_id', 0);
            $page = input('page', 0);
            if($customerId <= 0){
                return apiError(config('response.msg9'));
            }
            $shopId = model('api/Shop')->where(['user_id' => $this->getUserId()])->value('id');
            if(empty($shopId)){
                return apiError(config('response.msg10'));
            }
            $model = model('api/Order');
            $where = [
                'user_id' => $customerId,
                'shop_id' => $shopId,
                'order_status' => 1,
            ];
            $limit = config('parameters.page_size_level_2');
            $select = $model->field(['max(id) as id', 'free_rule_id'])->where($where)->limit($page * $limit, $limit)->order('id desc')->group('free_rule_id')->select();

            foreach($select as $key => &$value){
                $value['consume_amount'] = $model->where(['id' => $value['id']])->value('consume_amount');
                $where = [
                    'user_id' => $customerId,
                    'shop_id' => $shopId,
                    'free_rule_id' => $value['free_rule_id'],
                    'order_status' => 1,
                ];
                $detail = $model->field(['order_num', 'payment_time', 'payment_amount', 'order_amount', 'free_flag'])->order('id desc')->where($where)->select()->toArray();
                foreach($detail as $k => &$v){
                    $v['payment_amount'] = $v['payment_amount'] ?: '0.00';
                }
                $value['detail'] = $detail;
                unset($value['id']);
                unset($value['free_rule_id']);
            }
            $data = [
                'list' => $select
            ];
            return apiSuccess($data);

        }catch (\Exception $e){
            $logContent = '订单记录接口异常：' . $e->getMessage();
            generateApiLog($logContent);
        }
        return apiError();
    }


}
