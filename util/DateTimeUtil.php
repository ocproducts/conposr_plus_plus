<?php /*

 conposr_plus_plus
 Copyright (c) ocProducts, 2004-2019

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    conposr_plus_plus
 */

class DateTimeUtil
{
    const DAYS = 0; // integer
    const DATE_FORMAT = '%d/%m/%Y';
    const MYSQL_DATE_FORMAT = '%Y-%m-%d';
    const HTML5_DATE_FORMAT = '%Y-%m-%d';

    public static function formatDateTime($date) // [DateTime date]
    {
        if ($date === null) {
            return null;
        }
        $fDate = strftime(self::DATE_FORMAT, $date->getTimestamp());
        return $fDate;
    }

    public static function formatMySQLDateTime($date) // [DateTime date]
    {
        if ($date === null) {
            return null;
        }
        $fDate = strftime(self::MYSQL_DATE_FORMAT, $date->getTimestamp());
        return $fDate;
    }

    public static function formatHTML5DateTime($date) // [DateTime date]
    {
        if ($date === null) {
            return null;
        }
        $fDate = strftime(self::HTML5_DATE_FORMAT, $date->getTimestamp());
        return $fDate;
    }

    public static function dateTimeFromString($fDate) // [string fDate]
    {
        if ($fDate === null) {
            return null;
        }
        $timestamp = strtotime(str_replace('/', '-', $fDate));
        if ($timestamp === false) {
            Logger::error('Could not parse date ' . $fDate);
            $timestamp = 0;
        }

        $date = new DateTime();
        $date->setTimestamp($timestamp);
        return $date;
    }

    public static function setToDayEnd($date) // [DateTime date]
    {
        $_date = clone $date;
        $_date->setTime(23, 59, 59);
        return $_date;
    }

    public static function addYear($date, $years) // [DateTime date, integer years]
    {
        $_date = clone $date;
        $_date->add(new DateInterval('P' . $years . 'Y'));
        return $_date;
    }

    public static function addMonths($date, $months) // [DateTime date, integer months]
    {
        $_date = clone $date;
        $_date->add(new DateInterval('P' . $months . 'M'));
        return $_date;
    }

    public static function addDay($date, $days) // [DateTime date, integer days]
    {
        $_date = clone $date;
        $_date->add(new DateInterval('P' . $days . 'D'));
        return $_date;
    }

    public static function getDateDiff($d1, $d2, $type) // [DateTime d1, DateTime d2, integer type]
    {
        switch ($type) {
            case self::DAYS:
                $result = floatval($d2->getTimestamp() - $d1->getTimestamp());
                $result /= 60.0 * 60.0 * 24.0;
                return intval(floor($result));

            default:
                throw new CPPException('Unrecognised getDateDiff $type parameter');
        }
    }

    public static function setTime($date, $hours, $minutes) // [DateTime date, integer hours, integer minutes]
    {
        $_date = clone $date;
        $_date->setTimestamp(mkdir($hours, $minutes));
        return $_date;
    }

    public static function isPastDate($date) // [DateTime date]
    {
        return ($date->getTimestamp() <= time());
    }
}
