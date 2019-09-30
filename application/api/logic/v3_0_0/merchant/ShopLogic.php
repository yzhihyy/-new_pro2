<?php

namespace app\api\logic\v3_0_0\merchant;

use app\api\logic\BaseLogic;
use app\api\model\v2_0_0\OrderModel;
use app\api\model\v2_0_0\UserHasShopModel;
use app\api\model\v3_0_0\FollowRelationModel;
use app\api\model\v3_0_0\VideoModel;
use app\common\utils\date\DateHelper;

class ShopLogic extends BaseLogic
{
    /**
     * 粉丝数
     * @param array $params
     * @return float|string
     */
    public static function shopFansCount(array $params = [])
    {
       $model = model(FollowRelationModel::class);
       $count = $model->join('user u', 'from_user_id = u.id')->where(['to_shop_id' => $params['shop_id'], 'rel_type' => 1])->count(1);
       return $count;
    }

    /**
     * 店铺客户数
     * @param array $params
     * @return mixed
     */
    public static function shopCustomerCount(array $params = [])
    {
        $model = model(OrderModel::class);
        $count = $model->where(['shop_id' => $params['shop_id'], 'order_status' => 1])->group('user_id')->count(1);
        return $count;
    }

    /**
     * 粉丝信息
     * @param array $params
     * @return array
     */
    public static function fansInfo(array $params = [])
    {
        //昨日新增关注数
        $params = [
            'shop_id' => $params['shop_id'],
            'rel_type' => 1,
            'start_time' => DateHelper::getNowDateTime('-1 days')->format('Y-m-d').' 00:00:00',
            'end_time'   => DateHelper::getNowDateTime('-1 days')->format('Y-m-d'). ' 23:59:59',
        ];
        $fansAddYesCount = self::shopFansFollow($params);
        //近7日新增关注数
        $params['start_time'] = DateHelper::getNowDateTime('-7 days')->format('Y-m-d').' 00:00:00';
        $params['end_time']   = DateHelper::getNowDateTime('-1 days')->format('Y-m-d').' 23:59:59';
        $fansAddSevenCount = self::shopFansFollow($params);
        //昨日取消关注数
        $params['rel_type'] = 2;
        $params['start_time'] = DateHelper::getNowDateTime('-1 days')->format('Y-m-d').' 00:00:00';
        $params['end_time']   = DateHelper::getNowDateTime('-1 days')->format('Y-m-d').' 23:59:59';
        $fansCancelYesCount = self::shopFansFollow($params);
        //近7日取消关注数
        $params['rel_type'] = 2;
        $params['start_time'] = DateHelper::getNowDateTime('-7 days')->format('Y-m-d').' 00:00:00';
        $params['end_time']   = DateHelper::getNowDateTime('-1 days')->format('Y-m-d').' 23:59:59';
        $fansCancelSevenCount = self::shopFansFollow($params);
        $fansInfo = [
            'fans_add_yes_count'        => $fansAddYesCount,
            'fans_add_seven_count'      => $fansAddSevenCount,
            'fans_cancel_yes_count'     => $fansCancelYesCount,
            'fans_cancel_seven_count'   => $fansCancelSevenCount,
        ];
        return $fansInfo;
    }

    /**
     *
     * @param $params
     * @return int
     */
    public static function shopFansFollow(array $params = [])
    {
        $count = 0;
        if(!empty($params)){
            $model = model(FollowRelationModel::class);
            $query = $model->where(['to_shop_id' => $params['shop_id'], 'rel_type' => $params['rel_type']]);
            switch($params['rel_type']){
                case 1://关注
                    $count = $query->whereBetweenTime('generate_time', $params['start_time'], $params['end_time'])->count();
                    break;
                case 2://取消关注
                    $count = $query->whereBetweenTime('cancel_time', $params['start_time'], $params['end_time'])->count();
                    break;
            }
        }
        return $count;
    }


    /**
     * 视频
     * @param array $params
     * @return mixed
     * @throws
     */
    public static function shopVideoInfo(array $params = [])
    {
        $model = model(VideoModel::class);
        $field = 'COALESCE(SUM(play_count),0) AS play_count, COALESCE(SUM(like_count),0) AS like_count, COALESCE(SUM(share_count),0) AS share_count';
        $where = ['shop_id' => $params['shop_id']];
        $info = $model->where($where)->field($field)->find();
        return $info;
    }

    /**
     * 获取授权店铺列表
     * @param array $params
     * @return array
     */
    public static function authorizedShopList(array $params = [])
    {
        $shopPivotModel = model(UserHasShopModel::class);
        $shopPivot = $shopPivotModel->getAuthorizedShop(['userId' => $params['userId']]);
        $authorizedShopList = [];
        foreach ($shopPivot as $item) {
            $authorizedShopList[] = [
                'shop_id' => $item['shopId'],
                'shop_name' => $item['shopName'],
                'shop_image' => getImgWithDomain($item['shopImage']),
                'shop_thumb_image' => getImgWithDomain($item['shopThumbImage']),
                'selected_shop_flag' => $item['selectedShopFlag']
            ];
        }
        return $authorizedShopList;
    }


}
