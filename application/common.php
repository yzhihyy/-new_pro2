<?php

use think\facade\Config;
use think\facade\Log;
use think\facade\Cache;

if (!function_exists('jwtEncode')) {
    /**
     * jwt加密
     *
     * @param null $data
     *
     * @return string
     */
    function jwtEncode($data = null)
    {
        $key = Config::get('jwt.key');
        $time = time();
        $token = [
            'iss' => Config::get('jwt.iss'),
            'aud' => Config::get('jwt.aud'),
            'iat' => $time,
            'nbf' => $time,
            'exp' => $time + Config::get('jwt.exp'),
            'data' => $data
        ];
        $jwt = CustomJWT::encode($token, $key);
        return $jwt;
    }
}

if (!function_exists('jwtDecode')) {
    /**
     * jwt解密
     *
     * @param $jwt
     *
     * @return mixed
     */
    function jwtDecode($jwt)
    {
        $key = Config::get('jwt.key');
        $decoded = CustomJWT::decode($jwt, $key, ['HS256']);
        $decodedArray = json_decode(json_encode($decoded), true);
        return $decodedArray['data'];
    }
}

if (!function_exists('generateTelCode')) {
    /**
     * 生成短信验证码
     *
     * @param int $length
     *
     * @return string
     */
    function generateTelCode($length = 4)
    {
        $returnStr = '';
        $pattern = '123456789';
        for ($i = 0; $i < $length; $i++) {
            $returnStr .= $pattern{mt_rand(0, 8)};
        }
        return $returnStr;
    }
}

if (!function_exists('decimalAdd')) {
    /**
     * Add two arbitrary precision numbers.
     *
     * @param mixed $leftOperand The left operand
     * @param mixed $rightOperand The right operand
     * @param int $scale This optional parameter is used to set the number of digits after the decimal place in the result
     *
     * @return string
     */
    function decimalAdd($leftOperand, $rightOperand, $scale = 2)
    {
        return bcadd($leftOperand, $rightOperand, $scale);
    }
}

if (!function_exists('decimalSub')) {
    /**
     * Subtract one arbitrary precision number from another.
     *
     * @param mixed $leftOperand The left operand
     * @param mixed $rightOperand The right operand
     * @param int $scale This optional parameter is used to set the number of digits after the decimal place in the result
     *
     * @return string
     */
    function decimalSub($leftOperand, $rightOperand, $scale = 2)
    {
        return bcsub($leftOperand, $rightOperand, $scale);
    }
}

if (!function_exists('decimalMul')) {
    /**
     * Multiply two arbitrary precision numbers.
     *
     * @param mixed $leftOperand The left operand
     * @param mixed $rightOperand The right operand
     * @param int $scale This optional parameter is used to set the number of digits after the decimal place in the result
     *
     * @return string
     */
    function decimalMul($leftOperand, $rightOperand, $scale = 2)
    {
        return bcmul($leftOperand, $rightOperand, $scale);
    }
}

if (!function_exists('decimalDiv')) {
    /**
     * Divide two arbitrary precision numbers.
     *
     * @param mixed $dividend The dividend
     * @param mixed $divisor The divisor
     * @param int $scale This optional parameter is used to set the number of digits after the decimal place in the result
     *
     * @return string
     */
    function decimalDiv($dividend, $divisor, $scale = 2)
    {
        return bcdiv($dividend, $divisor, $scale);
    }
}

if (!function_exists('decimalComp')) {
    /**
     * Compare two arbitrary precision numbers
     *
     * @param mixed $leftOperand The left operand
     * @param mixed $rightOperand The right operand
     * @param int $scale The optional scale parameter is used to set the number of digits after the decimal place which will be used in the comparison
     *
     * @return string
     */
    function decimalComp($leftOperand, $rightOperand, $scale = 2)
    {
        return bccomp($leftOperand, $rightOperand, $scale);
    }
}

if (!function_exists('getImgWithDomain')) {
    /**
     * 获取带域名的图片链接
     *
     * @param $url
     * @param string $domain
     *
     * @return string
     */
    function getImgWithDomain($url, $domain = '')
    {
        if (empty($url)) {
            return '';
        }
        if (stripos($url, 'http://') !== false || stripos($url, 'https://') !== false) {
            return $url;
        } else {
            if (empty($domain)) {
                $domain = config('app.resources_domain');
            }
            return $domain . $url;
        }
    }
}

if (!function_exists('filterImgDomain')) {
    /**
     * 过滤图片域名
     *
     * @param string $imageUrl
     *
     * @return string
     */
    function filterImgDomain($imageUrl)
    {
        // 图片服务器域名
        $imageDomain = config('app.resources_domain');
        return str_replace($imageDomain, '', $imageUrl);
    }
}

if (!function_exists('generateApiLog')) {
    /**
     * 生成API日志
     *
     * @param $msg
     * @param string $type
     */
    function generateApiLog($msg, $type = 'error')
    {
        generateCustomLog($msg, '/api', $type);
    }
}

if (!function_exists('generateCustomLog')) {
    /**
     * 生成自定义日志
     *
     * @param $msg
     * @param string $type
     * @param string $path
     */
    function generateCustomLog($msg, $path = '', $type = 'error')
    {
        $logConfig = Config::pull('log');
        if (!empty($path)) {
            $logConfig['path'] = config('app.log_server_root_path') . $path;
            $logConfig['close'] = true;
        }
        Log::init($logConfig);
        Log::write($msg, $type);
    }
}

if (!function_exists('setCustomCache')) {
    /**
     * 生成自定义缓存
     *
     * @param $name
     * @param $value
     * @param int $expire
     *
     * @return boolean
     */
    function setCustomCache($name, $value, $expire = 0)
    {
        $cacheConfig = Config::pull('cache');
        $options = $cacheConfig['default'];
        $options['path'] = config('app.cache_server_root_path');
        return Cache::connect($options)->set($name, $value, $expire);
    }
}

if (!function_exists('getCustomCache')) {
    /**
     * 获取自定义缓存
     *
     * @param $name
     * @param $default
     *
     * @return mixed
     */
    function getCustomCache($name, $default = false)
    {
        $cacheConfig = Config::pull('cache');
        $options = $cacheConfig['default'];
        $options['path'] = config('app.cache_server_root_path');
        return Cache::connect($options)->get($name, $default);
    }
}

if (!function_exists('rmCustomCache')) {
    /**
     * 删除自定义缓存
     *
     * @param $name
     *
     * @return boolean
     */
    function rmCustomCache($name)
    {
        $cacheConfig = Config::pull('cache');
        $cacheConfig['path'] = config('app.cache_server_root_path');
        return Cache::connect($cacheConfig)->rm($name);
    }
}

if (!function_exists('dateFormat')) {
    /**
     * 时间格式化
     *
     * @param $timeStr
     * @param string $format
     *
     * @return string
     */
    function dateFormat($timeStr, $format = 'Y-m-d H:i')
    {
        if (empty($timeStr)) {
            return '';
        }
        $dateTime = new DateTime($timeStr);
        return $dateTime->format($format);
    }
}

if (!function_exists('dateTransformer')) {
    /**
     * 日期转换
     *
     * @param DateTime $dateTime
     * @param DateTime|null $current
     *
     * @return string
     * @throws Exception
     */
    function dateTransformer($dateTime, ?DateTime $current = null)
    {
        if (is_string($dateTime)) {
            $dateTime = new DateTime($dateTime);
        }

        // 本年之前
        if ($dateTime->format('Y') < date('Y')) {
            return $dateTime->format('Y-m-d');
        }

        // 日期格式化
        if (!$current) {
            $current = new DateTime();
        }

        // $dateTime 比 $current 大的情况
        if ($dateTime >= $current) {
            return $dateTime->format('Y-m-d');
        }

        $diff = $current->diff($dateTime);
        // 间隔的天数
        switch($diff->days) {
            case 0:
                // 间隔小于一个小时，最低单位1分钟
                if ($diff->h == 0) {
                    return ($diff->i > 0 ? $diff->i : 1) . '分钟前';
                }
                // 间隔大于1小时少于24小时
                return $diff->h . '小时前';
            case 1:
                return '昨天';
            default:
                return $dateTime->format('m-d');
        }
    }
}

if (!function_exists('mkdirs')) {
    /**
     * 递归创建目录
     *
     * @param $dir
     * @param int $mode
     *
     * @return bool
     */
    function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) {
            return true;
        }
        if (!mkdirs(dirname($dir), $mode)) {
            return false;
        }
        return @mkdir($dir, $mode);
    }
}

if (!function_exists('num2Ch')) {
    /**
     * 数字转为汉字描述、人民币大写
     *
     * · 个，十，百，千，万，十万，百万，千万，亿，十亿，百亿，千亿，万亿，十万亿，
     *   百万亿，千万亿，兆；此函数亿乘以亿为兆
     *
     * · 以「十」开头，如十五，十万，十亿等。两位数以上，在数字中部出现，则用「一十几」，
     *   如一百一十，一千零一十，一万零一十等
     *
     * · 「二」和「两」的问题。两亿，两万，两千，两百，都可以，但是20只能是二十，
     *   200用二百也更好。22,2222,2222是「二十二亿两千二百二十二万两千二百二十二」
     *
     * · 关于「零」和「〇」的问题，数字中一律用「零」，只有页码、年代等编号中数的空位
     *   才能用「〇」。数位中间无论多少个0，都读成一个「零」。2014是「两千零一十四」，
     *   20014是「二十万零一十四」，201400是「二十万零一千四百」
     *
     * @param minx $number
     * @param boolean $isRmb
     *
     * @return string
     * @throws Exception
     * @author https://github.com/wilon
     */
    function num2Ch($number, $isRmb = false)
    {
        // 判断正确数字
        if (!preg_match('/^-?\d+(\.\d+)?$/', $number)) {
            throw new Exception('num2Ch() wrong number', 1);
        }
        list($integer, $decimal) = explode('.', $number . '.0');
        // 检测是否为负数
        $symbol = '';
        if (substr($integer, 0, 1) == '-') {
            $symbol = '负';
            $integer = substr($integer, 1);
        }
        if (preg_match('/^-?\d+$/', $number)) {
            $decimal = null;
        }
        $integer = ltrim($integer, '0');
        // 准备参数
        $numArr  = ['', '一', '二', '三', '四', '五', '六', '七', '八', '九', '.' => '点'];
        $descArr = ['', '十', '百', '千', '万', '十', '百', '千', '亿', '十', '百', '千', '万亿', '十', '百', '千', '兆', '十', '百', '千'];
        if ($isRmb) {
            $number = substr(sprintf("%.5f", $number), 0, -1);
            $numArr  = ['', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖', '.' => '点'];
            $descArr = ['', '拾', '佰', '仟', '万', '拾', '佰', '仟', '亿', '拾', '佰', '仟', '万亿', '拾', '佰', '仟', '兆', '拾', '佰', '仟'];
            $rmbDescArr = ['角', '分', '厘', '毫'];
        }
        // 整数部分拼接
        $integerRes = '';
        $count = strlen($integer);
        if ($count > max(array_keys($descArr))) {
            throw new Exception('num2Ch() number too large.', 1);
        } else if ($count == 0) {
            $integerRes = '零';
        } else {
            for ($i = 0; $i < $count; $i++) {
                $n = $integer[$i];      // 位上的数
                $j = $count - $i - 1;   // 单位数组 $descArr 的第几位
                // 零零的读法
                $isLing = $i > 1                    // 去除首位
                    && $n !== '0'                   // 本位数字不是零
                    && $integer[$i - 1] === '0';    // 上一位是零
                $cnZero = $isLing ? '零': '';
                $cnNum  = $numArr[$n];
                // 单位读法
                $isEmptyDanwei = ($n == '0' && $j % 4 != 0)     // 是零且一断位上
                    || substr($integer, $i - 3, 4) === '0000';  // 四个连续0
                $descMark = isset($cnDesc) ? $cnDesc : '';
                $cnDesc = $isEmptyDanwei ? '' : $descArr[$j];
                // 第一位是一十
                if ($i == 0 && $cnNum == '一' && $cnDesc == '十') $cnNum = '';
                // 二两的读法
                $isChangeEr = $n > 1 && $cnNum == '二'       // 去除首位
                    && !in_array($cnDesc, ['', '十', '百'])  // 不读两\两十\两百
                    && $descMark !== '十';                   // 不读十两
                if ($isChangeEr ) $cnNum = '两';
                $integerRes .=  $cnZero . $cnNum . $cnDesc;
            }
        }
        // 小数部分拼接
        $decimalRes = '';
        $count = strlen($decimal);
        if ($decimal === null) {
            $decimalRes = $isRmb ? '整' : '';
        } else if ($decimal === '0') {
            $decimalRes = '零';
        } else if ($count > max(array_keys($descArr))) {
            throw new Exception('num2Ch() number too large.', 1);
        } else {
            for ($i = 0; $i < $count; $i++) {
                if ($isRmb && $i > count($rmbDescArr) - 1) break;
                $n = $decimal[$i];
                $cnZero = $n === '0' ? '零' : '';
                $cnNum  = $numArr[$n];
                $cnDesc = $isRmb ? $rmbDescArr[$i] : '';
                $decimalRes .=  $cnZero . $cnNum . $cnDesc;
            }
        }
        // 拼接结果
        $res = $symbol . ($isRmb ?
                $integerRes . ($decimalRes === '零' ? '元整' : "元$decimalRes"):
                $integerRes . ($decimalRes ==='' ? '' : "点$decimalRes"));

        return $res;
    }
}

if (!function_exists('generateEasemobPassword')) {
    function generateEasemobPassword($length = 8)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }
}


if (!function_exists('validateApplePay')) {
    /**
     * 验证AppStore内付
     * @param  string $receipt_data 付款后凭证
     * @return array                验证是否成功
     */
    function validateApplePay($receipt_data){
        /**
         * 21000 App Store不能读取你提供的JSON对象
         * 21002 receipt-data域的数据有问题
         * 21003 receipt无法通过验证
         * 21004 提供的shared secret不匹配你账号中的shared secret
         * 21005 receipt服务器当前不可用
         * 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
         * 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
         * 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务
         */
        function acurl($receipt_data, $sandbox=0){
            //小票信息
            $POSTFIELDS = array("receipt-data" => $receipt_data);
            $POSTFIELDS = json_encode($POSTFIELDS);

            //正式购买地址 沙盒购买地址
            $url_buy     = "https://buy.itunes.apple.com/verifyReceipt";
            $url_sandbox = "https://sandbox.itunes.apple.com/verifyReceipt";
            $url = $sandbox ? $url_sandbox : $url_buy;

            //简单的curl
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);   //这两行一定要加，不加会报SSL 错误  
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }
        // 验证参数
        if (strlen($receipt_data)<20){
            $result=array(
                'status'=>false,
                'message'=>'非法参数'
                );
            return $result;
        }
        // 请求验证
        $html = acurl($receipt_data);
        $data = json_decode($html,true);

        // 如果是沙盒数据 则验证沙盒模式
        if($data['status']=='21007'){
            // 请求验证
            $html = acurl($receipt_data, 1);
            $data = json_decode($html,true);
            $data['sandbox'] = '1';
        }

        // 判断是否购买成功
        if(intval($data['status'])===0){
            $result = [
                'status'=>true,
                'message'=>'购买成功',
                'data' => $data
            ];
        }else{
            $result = [
                'status'=>false,
                'message'=>'购买失败 status:'.$data['status'],
                'data' => $data
            ];
        }
        return $result;
    }
}