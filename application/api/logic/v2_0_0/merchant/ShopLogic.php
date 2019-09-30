<?php

namespace app\api\logic\v2_0_0\merchant;

use app\api\logic\BaseLogic;
use app\api\model\v2_0_0\ShopNodeModel;
use app\api\service\UploadService;
use app\common\utils\date\DateHelper;

class ShopLogic extends BaseLogic
{
    /**
     * 获取店铺信息
     *
     * @param array $shop
     *
     * @return array
     */
    public function getShopInfo($shop)
    {
        $fields = [
            'shop_id',
            'shop_name',
            'shop_phone',
            //'shop_address',
            'shop_address_poi',
            'shop_province',
            'shop_city',
            'shop_area',
            'shop_detail_address',
            'longitude',
            'latitude',
            'merchant_name',
            'identity_card_number',
            'inviter',
            'identity_card_front_face',
            'identity_card_back_face',
            'business_license',
            'shop_logo',
            'remark',
            'online_status',
        ];
        if (empty($shop)) {
            $responseData = array_fill_keys($fields, '');
            $responseData['shop_id'] = 0;
            $responseData['online_status'] = -1;
        } else {
            $responseData = array_combine($fields, [
                $shop['shopId'],
                $shop['shopName'],
                $shop['shopPhone'],
                //$shop['shopAddress'],
                $shop['shopAddressPoi'],
                $shop['shopProvince'],
                $shop['shopCity'],
                $shop['shopArea'],
                $shop['shopDetailAddress'],
                $shop['longitude'],
                $shop['latitude'],
                $shop['realName'],
                $shop['idNumber'],
                $shop['inviter'],
                getImgWithDomain($shop['identityCardFrontFaceImg']),
                getImgWithDomain($shop['identityCardBackFaceImg']),
                getImgWithDomain($shop['identityCardHolderHalfImg']),
                getImgWithDomain($shop['shopImage']),
                $shop['remark'],
                $shop['onlineStatus'],
            ]);
        }

        $responseData['customer_service_phone'] = config('parameters.customer_service_phone');

        return $responseData;
    }

    /**
     * 保存店铺/分店申请
     *
     * @param int $type 申请类型 1:店铺, 2:分店
     * @param int $userId
     * @param array $paramsArray
     * @param array|null $shop
     * @param $shopModel
     */
    public function saveShop(int $type, int $userId, array $paramsArray, $shop, $shopModel)
    {
        $shopImage = !empty($paramsArray['shop_logo']) ? filterImgDomain($paramsArray['shop_logo']) : config('parameters.merchant_shop_default_logo_img');
        $shopData = [
            'user_id' => $userId,
            'shop_name' => $paramsArray['shop_name'],
            'phone' => $paramsArray['shop_phone'],
            //'shop_address' => $paramsArray['shop_address'],
            'shop_address' => $paramsArray['shop_province'] . $paramsArray['shop_city'] . $paramsArray['shop_area'] . $paramsArray['shop_detail_address'],
            'shop_address_poi' => $paramsArray['shop_address_poi'],
            'shop_province' => $paramsArray['shop_province'],
            'shop_city' => $paramsArray['shop_city'],
            'shop_area' => $paramsArray['shop_area'],
            'shop_detail_address' => $paramsArray['shop_detail_address'],
            'longitude' => $paramsArray['longitude'],
            'latitude' => $paramsArray['latitude'],
            'real_name' => $paramsArray['merchant_name'],
            'id_number' => $paramsArray['identity_card_number'],
            'inviter' => $paramsArray['inviter'],
            'identity_card_front_face_img' => isset($paramsArray['identity_card_front_face']) ? filterImgDomain($paramsArray['identity_card_front_face']) : '',
            'identity_card_back_face_img' => isset($paramsArray['identity_card_back_face']) ? filterImgDomain($paramsArray['identity_card_back_face']) : '',
            'identity_card_holder_half_img' => isset($paramsArray['business_license']) ? filterImgDomain($paramsArray['business_license']) : '',
            'shop_image' => $shopImage,
            'shop_thumb_image' => (new UploadService())->getImageThumbName($shopImage, 2),
            'online_status' => 2,
        ];
        if (empty($shop)) {
            $shopData['shop_type'] = $type;
            $shopData['generate_time'] = date('Y-m-d H:i:s');
            $shopModel::create($shopData);
        } else {
            $shopModel::update($shopData, ['id' => $shop['id']]);
        }
    }

    /**
     * 获取商家无权限的节点(目前只获取无权限的菜单)
     *
     * @param string $authorizedRule
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getShopNoPermissionsNodes(string $authorizedRule)
    {
        $noPermissionsNodes = [];
        // -1代表拥有全部权限
        if ($authorizedRule !== '-1') {
            /** @var ShopNodeModel $shopNodeModel */
            $shopNodeModel = model(ShopNodeModel::class);
            // 节点列表
            $nodes = $shopNodeModel->getShopNodes(['isMenu' => 1]);
            // 节点ID数组
            $nodesIdArray = array_column($nodes, 'nodeId');
            // 商家节点ID数组
            $shopNodesIdArray = explode(',', $authorizedRule);
            // 商家无权限的菜单ID数组
            $noPermissionsMenuIdArray = array_diff($nodesIdArray, $shopNodesIdArray);
            foreach ($nodes as $node) {
                if (in_array($node['id'], $noPermissionsMenuIdArray)) {
                    $noPermissionsNodes[] = [
                        'node_id' => $node['nodeId'],
                        'node_name' => $node['nodeName'],
                        'node_identifier' => $node['actionAlias'],
                    ];
                }
            }
        }

        return $noPermissionsNodes;
    }

    /**
     * 处理明细记录
     *
     * @param array $transactions
     *
     * @return array
     */
    public function transactionsHandle($transactions)
    {
        $responseData = [];
        // 明细类型说明
        $typeDescArray = [
            1 => '消费买单',
            2 => '消费买单',
            3 => '提现',
            6 => '预存金额',
            7 => '预约定金',
        ];
        // 当前年份
        $currentYear = date('Y');
        // 银行和第三方支付图标
        $paymentIconArray = config('parameters.payment_icon');
        foreach ($transactions as $item) {
            $item['generateTime'] = DateHelper::getNowDateTime($item['generateTime']);
            // 默认显示图标
            $recordIcon = $item['avatar'];
            $recordTitle = $item['nickname'];
            // 明细类型
            $type = $item['type'];
            // 明细状态描述
            $statusDesc = '';
            // 提现
            if ($item['type'] == 3) {
                if (isset($paymentIconArray[$item['bankCardType']])) {
                    $recordIcon = $paymentIconArray[$item['bankCardType']];
                }

                $recordTitle = $item['bankCardType'];
                $statusDesc = $item['status'] == 1 ? '审核中' : ($item['status'] == 2 ? '失败' : '成功');
            }

            $dateFormat = 'm月d日 H:i';
            if ($item['generateTime']->format('Y') != $currentYear) {
                $dateFormat = "Y年{$dateFormat}";
            }

            $symbol = $this->isInflowTransaction($type) ? '+' : ($this->isOutflowTransaction($type, $item['status']) ? '-' : '');
            $responseData[] = [
                'record_id' => $item['recordId'],
                'record_icon' => getImgWithDomain($recordIcon),
                'record_title' => $recordTitle,
                'record_time' => $item['generateTime']->format($dateFormat),
                'record_type' => $item['type'],
                'record_type_desc' => $typeDescArray[$type] . $statusDesc,
                'record_flag' => $symbol == '+' ? 1 : ($symbol == '-' ? 2 : 3),
                'record_amount' => $symbol . $item['amount'],
                'theme_activity_id' => $item['themeActivityId'],
                'theme_activity_title' => $item['themeActivityTitle'],
            ];
        }

        return $responseData;
    }

    /**
     * 是否是收入明细
     *
     * @param int $type 明细记录类型
     * 2:商家收入
     *
     * @return bool
     */
    public function isInflowTransaction($type)
    {
        return in_array($type, [2, 6, 7]);
    }

    /**
     * 是否是支出明细
     *
     * @param int $type 明细记录类型 1:用户支出,3:商家提现
     * @param int $status 状态 1：审核中, 2：审核失败, 3：审核成功或完成
     *
     * @return bool
     */
    public function isOutflowTransaction($type, $status)
    {
        // 如果是提现失败则不算支出
        return (in_array($type, [3]) && $status != 2) || (in_array($type, [1]));
    }

    /**
     * 相册图片参数校验
     *
     * @param mixed $imageList
     *
     * @return array
     */
    public function albumParamsValidate($imageList)
    {
        // 图片数组
        $imageList = json_decode($imageList, true);
        if (empty($imageList)) {
            return $this->logicResponse(config('response.msg38'));
        }

        // 图片数量限制
        $imageNumsLimit = config('parameters.upload_max_count_level_9');
        // 图片不能超过%d张
        if (count($imageList) > $imageNumsLimit) {
            return $this->logicResponse(sprintf(config('response.msg40'), $imageNumsLimit));
        }

        $coverImage = [];
        $imageArray = [];
        $uploadService = new UploadService();
        foreach ($imageList as $item) {
            if (isset($item['a_cover']) && isset($item['a_img']) && !empty($item['a_img'])) {
                $albumId = (int)$item['a_id'];
                $image = filterImgDomain($item['a_img']);
                $thumbImage = $uploadService->getImageThumbName($image);
                $cover = $item['a_cover'] && empty($coverImage) ? 1 : 0;
                $imageArray[] = [
                    'id' => $albumId,
                    'image' => $image,
                    'thumb_image' => $thumbImage,
                    'cover_flag' => $cover,
                ];
                if (empty($coverImage) && $cover) {
                    $coverImage = [
                        'id' => $albumId,
                        'image' => $image,
                        'thumb_image' => $thumbImage,
                    ];
                }
            }
        }
        if (empty($imageArray)) {
            return $this->logicResponse(config('response.msg38'));
        } elseif (empty($coverImage)) {
            $imageArray[0]['cover_flag'] = 1;
            $coverImage = $imageArray[0];
        }

        return $this->logicResponse([], [
            'coverImage' => $coverImage,
            'imageList' => $imageArray
        ]);
    }
}