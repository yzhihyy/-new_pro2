<?php

namespace app\common\utils\string;

class StringHelper
{
    /**
     * 生成编号
     * 由 $prefix + 年月日(8位) + 时分秒(6位) + 毫秒数(3位) + 随机数(4位) 组成
     * 如： 201503111225301201234
     *
     * @param string $prefix 前缀
     *
     * @return string
     */
    public static function generateNum($prefix = '')
    {
        $rndStr = date('YmdHis');
        list($mt, $tm) = explode(' ', microtime());
        $millisecondsStr = str_pad(intval($mt * 1000), 3, '0', STR_PAD_LEFT);
        $rnd = rand(1000, 9999);
        return $prefix . $rndStr . $millisecondsStr . $rnd;
    }

    /**
     * null转化为空字符串
     *
     * @param array $params
     *
     * @return array
     */
    public static function nullValueToEmptyValue($params)
    {
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    $params[$key] = self::nullValueToEmptyValue($value);
                } else {
                    if (is_null($value) || (!is_object($value) && strtolower($value) === 'null')) {
                        $params[$key] = '';
                    }
                }
            }
            return $params;
        } elseif (is_object($params)) {

        } elseif (is_null($params) || strtolower($params) === 'null') {
            return '';
        }
    }

    /**
     * 数组转换成xml
     *
     * @param array $params
     *
     * @return string
     */
    public static function toXml($params)
    {
        $xml = "<xml>";
        foreach ($params as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";

        return $xml;
    }

    /**
     * XML转换成数组
     *
     * @param string $xml
     *
     * @return array
     */
    public static function xmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        $xmlObj = simplexml_load_string(
            $xml,
            'SimpleXMLIterator',//可迭代对象
            LIBXML_NOCDATA
        );

        $arr = [];
        //指针指向第一个元素
        $xmlObj->rewind();
        while (1) {
            if (! is_object($xmlObj->current())) {
                break;
            }
            $arr[$xmlObj->key()] = $xmlObj->current()->__toString();
            //指向下一个元素
            $xmlObj->next();
        }
        return $arr;
    }

    /**
     * 隐藏部分字符串
     *
     * @param string $string
     * @param int $startLen 前面几位
     * @param int $endLen 后面几位
     * @param string $hideStr 隐藏的字符串
     *
     * @return string
     */
    public static function hidePartOfString($string, $startLen = 3, $endLen = 4, $hideStr = '**')
    {
        $hide = '';
        $length = mb_strlen($string);
        if ($length == 1) {
            $hide .= $string . $hideStr; // 如果长度为1，则直接加隐藏字符串
        } else {
            $hide .= mb_substr($string, 0, $startLen, 'utf-8') . $hideStr;// 取得第一个字符
            $hide .= mb_substr($string, $length - $endLen, $endLen, 'utf-8');// 取得最后一个字符
        }

        return $hide;
    }

    /**
     * 字符串(数组)转为下划线
     *
     * @param $params
     * @param string $separator
     * @return string
     */
    public static function snakeCase($params, string $separator = '_')
    {
        $data = [];
        if (is_string($params)) {
            $data = strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $params));
        }
        if (is_array($params)) {

            foreach ($params as $key => $value) {
                $k = $key;
                if (preg_match('/^[A-Za-z]+$/', $key)) {
                    $k = strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $key));
                }
                $data[$k] = $value;

                if (is_array($value)) {
                    $data[$k] = self::snakeCase($value);
                }
            }
        }

        return $data;
    }

    /**
     * 字符串(数组)转为驼峰
     *
     * @param string|array $value
     * @param string|array $separator
     *
     * @return string|array
     */
    public static function camelCase($value, $separator = ['-', '_'])
    {
        if (is_string($value)) {
            $value = lcfirst(str_replace(' ', '', ucwords(str_replace($separator, ' ', $value))));
        } elseif (is_array($value)) {
            foreach ($value as &$item) {
                $item = self::camelCase($item);
            }
        }

        return $value;
    }

    /**
     * 检测是否是正整数数字或者正整数字符串
     *
     * @param int|string|array $param
     *
     * @return bool
     */
    public static function isPositiveInteger($param)
    {
        $validator = function ($var) {
            if ((is_int($var) || ctype_digit($var)) && $var > 0) {
                return true;
            }
            return false;
        };

        if (is_array($param)) {
            foreach ($param as $key => $value) {
                $bool = $validator($value);
                if (! $bool) {
                    return false;
                }
            }
        } else {
            return $validator($param);
        }

        return true;
    }

    /**
     * 生成唯一的字符串
     *
     * @param int $limit
     *
     * @return string
     */
    public static function uniqueCode($limit = 10)
    {
        return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
    }
}
