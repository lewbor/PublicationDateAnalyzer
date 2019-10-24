<?php


namespace App\Lib\Utils;


class StringUtils
{
    public static function contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}