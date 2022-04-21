<?php
declare(strict_types=1);

namespace LinkCloud\Fast\Hyperf\Helpers;

class RandomStringHelper
{
    /**
     * @param int $length
     * @return string
     */
    public static function randomStr(int $length = 6): string
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $returnStr = '';

        for ($i = 0; $i < $length; $i++) {
            $returnStr .= $pattern[mt_rand(0, 61)];
        }

        return $returnStr;
    }

    /**
     * @param int $length
     * @return string
     */
    public static function randomNumberStr(int $length = 6): string
    {
        $pattern = '1234567890';
        $returnStr = '';

        for ($i = 0; $i < $length; $i++) {
            $returnStr .= $pattern[mt_rand(0, 9)];
        }

        return $returnStr;
    }

    /**
     * @param int $length
     * @return string
     */
    public static function randomAlphaStr(int $length = 6): string
    {
        $pattern = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $returnStr = '';

        for ($i = 0; $i < $length; $i++) {
            $returnStr .= $pattern[mt_rand(0, 51)];
        }

        return $returnStr;
    }
}
