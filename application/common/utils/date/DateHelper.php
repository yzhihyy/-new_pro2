<?php

namespace app\common\utils\date;

use DateTime;

class DateHelper
{
    /**
     * 取得现在时间
     *
     * @param string $date
     *
     * @return DateTime
     */
    public static function getNowDateTime($date = null)
    {
        if (!$date) {
            return new DateTime();
        } else {
            return new DateTime($date);
        }
    }

    /**
     * 取得现在时间戳
     *
     * @param string $date
     *
     * @return string
     */
    public static function getNowTimestamp($date = null)
    {
        if (!$date) {
            return self::getNowDateTime()->getTimestamp();
        } else {
            return self::getNowDateTime($date)->getTimestamp();
        }
    }

    /**
     * 格式化时间
     *
     * @param null|string $date
     * @param string $format
     *
     * @return bool|DateTime
     */
    public static function createFromFormat($date = null, $format = 'Y-m-d H:i:s')
    {
        if (!$date) {
            $date = date($format);
        }
        return DateTime::createFromFormat($format, $date);
    }

    /**
     * 获取两个日期间隔的天数
     *
     * @param DateTime $date
     * @param DateTime|null $current
     *
     * @return int
     */
    public static function getIntervalDays(DateTime $date, ?DateTime $current = null)
    {
        if (!$current) {
            $current = self::getNowDateTime();
        }
        // 重置时间部分，以防时间比较
        $currentDate = $current->setTime(0, 0, 0);
        $matchDate = clone $date;
        $matchDate->setTime(0, 0, 0);
        $diff = $currentDate->diff($matchDate);
        // 计算间隔的天数
        return (int) $diff->format('%R%a');
    }
}
