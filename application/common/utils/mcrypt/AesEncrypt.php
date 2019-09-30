<?php

namespace app\common\utils\mcrypt;

/**
 * AesEncrypt Class
 * API header parameters crypt
 */
class AesEncrypt
{

    const KEY = "a0da4e1c8061fb86";

    const IV = "7d1e9decb8160df7";

    /**
     * API数据加密
     *
     * @param type $str
     * @param type $iv
     * @param type $key
     *
     * @return string
     */
    public function aes128cbcEncrypt($str, $iv = self::IV, $key = self::KEY)
    {
        // $this->addPkcs7Padding($str,16)	//128算法加密
        $base = (openssl_encrypt($this->addPkcs7Padding($str), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv));//$this->addPkcs7Padding($str):进行补码
        return $this->strToHex($base);
    }

    /**
     * API数据解密
     *
     * @param type $encryptedText
     * @param type $iv
     * @param type $key
     *
     * @return type
     */
    public function aes128cbcHexDecrypt($encryptedText, $iv = self::IV, $key = self::KEY)
    {
        //128算法解密
        $str = $this->hexToStr($encryptedText);
        //ios端必须加base64_decode才能解密
        return base64_decode((openssl_decrypt($str, "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv)));
    }

    /**
     * 明文加工处理
     *
     * @param type $string
     * @param type $blocksize
     *
     * @return string
     */
    private function addPkcs7Padding($string, $blocksize = 32)
    {
        $len = strlen($string); //取得字符串长度
        $pad = $blocksize - ($len % $blocksize); //取得补码的长度
        $string .= str_repeat(chr($pad), $pad); //用ASCII码为补码长度的字符， 补足最后一段
        return $string;
    }

    /**
     * 字符串转十六进制
     *
     * @param type $string
     *
     * @return string
     */
    private function strToHex($string)
    {
        $hex = "";
        $tmp = "";
        for ($i = 0; $i < strlen($string); $i++) {
            $tmp = dechex(ord($string[$i]));
            $hex .= strlen($tmp) == 1 ? "0" . $tmp : $tmp;
        }
        $hex = strtoupper($hex);
        return $hex;
    }

    /**
     * 十六进制转字符串
     *
     * @param type $hex
     *
     * @return type
     */
    private function hexToStr($hex)
    {
        $string = "";
        for ($i = 0; $i < strlen($hex) - 1; $i += 2)
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        return $string;
    }
}