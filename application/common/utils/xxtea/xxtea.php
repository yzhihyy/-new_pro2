<?php

namespace app\common\utils\xxtea;
/**
 * xxtea 可逆加密处理类
 * 当数据需要可逆加密处理的时候可以考虑这个
 */
class xxtea {
    /**
     * 使用一个密钥对数据进行可逆加密
     * @param string $str
     * @param string $key 默认值
     *
     * @return string 加密的密文 base64
     */
    public static function encrypt($str, $key='%*(A^G*(&^*&&D&%F*(S') {
        if ($str == '') {
            return '';
        }
        $v = self::str2long($str, TRUE);
        $k = self::str2long($key, FALSE);
        $n = count($v) - 1;

        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = 0;
        while ( 0 < $q -- ) {
            $sum = self::int32($sum + $delta);
            $e = $sum >> 2 & 3;
            for($p = 0; $p < $n; $p ++) {
                $y = $v[$p + 1];
                $mx = @self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ @self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $z = $v[$p] = self::int32($v[$p] + $mx);
            }
            $y = $v[0];
            $mx = @self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ @self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$n] = self::int32($v[$n] + $mx);
        }
        $str = self::long2str($v, FALSE);
        $str = base64_encode($str);
        return $str;
    }

    /**
     * 利用固定密钥解密资料信息
     * @param string $str
     * @param string $key
     *
     * @return string 返回资料明文
     */
    public static function decrypt($str, $key='%*(A^G*(&^*&&D&%F*(S') {
        if ($str == "") {
            return "";
        }
        $str = base64_decode($str);
        $v   = self::str2long($str, FALSE);
        $k   = self::str2long($key, FALSE);
        $n   = count($v) - 1;

        $z   = $v[$n];
        $y   = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = self::int32($q * $delta);
        while ( $sum != 0 ) {
            $e = $sum >> 2 & 3;
            for($p = $n; $p > 0; $p --) {
                $z = $v[$p - 1];
                $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $y = $v[$p] = self::int32($v[$p] - $mx);
            }
            $z = $v[$n];
            $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y = $v[0] = self::int32($v[0] - $mx);
            $sum = self::int32($sum - $delta);
        }
        return self::long2str($v, TRUE);
    }

    /**
     * 整形转字符串
     * @param $v
     * @param $w
     * @return bool|string
     */
    private static function long2str($v, $w) {
        $len = count($v);
        $s = array();
        for($i = 0; $i < $len; $i ++) {
            $s[$i] = pack("V", $v[$i]);
        }
        if ($w) {
            return substr(join('', $s), 0, $v[$len - 1]);
        } else {
            return join('', $s);
        }
    }

    /**
     * 字符串转整形
     * @param $s
     * @param $w
     * @return array
     */
    private static function str2long($s, $w) {
        $v = unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3));
        $v = array_values($v);
        if ($w) {
            $v[count($v)] = strlen($s);
        }
        return $v;
    }

    /**
     * 工具函数转成32位整形
     * @param int $n
     * @return int
     */
    private static function int32($n) {
        while ($n >= 2147483648) {
            $n -= 4294967296;
        }
        while ($n <= - 2147483649) {
            $n += 4294967296;
        }
        return (int)$n;
    }
}