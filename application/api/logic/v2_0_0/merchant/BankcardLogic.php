<?php

namespace app\api\logic\v2_0_0\merchant;

use app\api\logic\BaseLogic;
use app\common\utils\date\DateHelper;
use app\common\utils\mcrypt\AesEncrypt;

class BankcardLogic extends BaseLogic
{
    /**
     * 银行卡号|身份证号校验
     *
     * @param array $paramsArray
     *
     * @return array
     */
    public function bankcardIdCardVerify($paramsArray)
    {
        // 取得加密对象
        $aes = new AesEncrypt();
        if (!empty($paramsArray['bankcard_num'])) {
            // 加密的银行卡号解密
            $paramsArray['bankcard_num'] = $aes->aes128cbcHexDecrypt($paramsArray['bankcard_num']);
            // 银行卡号校验
            if (!is_numeric($paramsArray['bankcard_num']) || mb_strlen($paramsArray['bankcard_num']) < 16) {
                return $this->logicResponse(config('response.msg25'));
            }
        }

        if (!empty($paramsArray['identity_card_num'])) {
            $paramsArray['identity_card_num'] = $aes->aes128cbcHexDecrypt($paramsArray['identity_card_num']);
            // 身份证号校验
            if (!preg_match(config('parameters.idcard_number_regex'), $paramsArray['identity_card_num'])) {
                return $this->logicResponse(config('response.msg26'));
            }
        }

        return $this->logicResponse([], $paramsArray);
    }

    /**
     * 校验银行卡缓存次数
     *
     * @param int $shopId
     *
     * @return string|null
     */
    public function bankcardQueryCountVerify($shopId)
    {

        // 当前日期
        $nowDate = DateHelper::getNowDateTime()->format('Ymd');
        $bankcardVerifyCacheName = sprintf(config('juhe.shop_bankcard_verify_cache_name'), $shopId, $nowDate);
        // 读取用户银行卡校验次数缓存信息
        $cacheArray = unserialize(getCustomCache($bankcardVerifyCacheName));
        if (!empty($cacheArray)) {
            $bankcardVerifyCount = config('juhe.bankcard_verify_count');
            // 判断银行卡校验次数是否超出限制次数
            if ($cacheArray['shop_id'] == $shopId && $cacheArray['count'] >= $bankcardVerifyCount) {
                return sprintf(config('response.msg27'), config('juhe.bankcard_verify_count'));
            }

            $cacheArray['count'] += 1;
        } else {
            $cacheArray = ['shop_id' => $shopId, 'count' => 1];
        }

        // 缓存用户银行卡校验次数信息
        setCustomCache($bankcardVerifyCacheName, serialize($cacheArray));
    }

    /**
     * 银行卡四元素缓存信息
     *
     * @param int $shopId
     * @param string $bankcardNum
     * @param array $result 银行卡四元素接口返回的数组
     * @param string $method
     *
     * @return array|bool|null
     */
    public function bankcard4Cache($shopId, $bankcardNum, $result = [], $method = 'get')
    {
        // 缓存银行卡信息
        $bankcardInfoFileName = sprintf(config('juhe.shop_bankcard_info_cache_name'), $shopId, $bankcardNum);
        // 读取银行卡缓存信息
        $cacheArray = unserialize(getCustomCache($bankcardInfoFileName));
        if ($method == 'get') {
            return $cacheArray;
        }
        elseif ($method == 'set' && !empty($result)) {
            // 银行卡信息数组
            $bankcardInfoArray = [
                'phone' => $result['mobile'],
                'holder_name' => $result['realname'],
                'bankcard_num' => $result['bankcard'],
                'idcard' => $result['idcard']
            ];
            if (!empty($cacheArray)) {
                $bankcardInfoArray = array_merge($cacheArray, $bankcardInfoArray);
            }

            // 缓存银行卡信息
            setCustomCache($bankcardInfoFileName, serialize($bankcardInfoArray));
        }
    }

    /**
     * 缓存银行卡类别信息
     *
     * @param int $shopId
     * @param array $bankcardNum
     * @param array $result 银行卡类别
     */
    public function bankcardSilkCache($shopId, $bankcardNum, $result)
    {
        // 保存银行卡类型信息到缓存文件
        $bankcardInfoFileName = sprintf(config('juhe.shop_bankcard_info_cache_name'), $shopId, $bankcardNum);
        // 读取银行卡缓存信息
        $cacheArray = unserialize(getCustomCache($bankcardInfoFileName));
        $bankcardInfoArray = [
            'bankcard_num' => $bankcardNum,
            'bank' => $result['bank'],
            'type' => $result['type'],
            'logo' => $result['logo']
        ];
        if (!empty($cacheArray)) {
            $bankcardInfoArray = array_merge($cacheArray, $bankcardInfoArray);
        }

        // 缓存银行卡信息
        setCustomCache($bankcardInfoFileName, serialize($bankcardInfoArray));
    }
}