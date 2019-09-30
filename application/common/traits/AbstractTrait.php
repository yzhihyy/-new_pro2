<?php

namespace app\common\traits;

trait AbstractTrait
{
    /**
     * Decimals on price.
     *
     * @param mixed $number
     * @param bool $removeExcessZero 是否去除小数点后多余的0
     * @param int $scale This optional parameter is used to set the number of digits after the decimal place in the result
     *
     * @return string
     */
    public function decimalFormat($number, $removeExcessZero = false, $scale = 2)
    {
        $number = bcadd($number, 0, $scale);
        if ($removeExcessZero) {
            $number = (string)(float)$number;
        }
        return $number;
    }
}
