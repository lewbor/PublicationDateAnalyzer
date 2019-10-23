<?php


namespace App\Lib;


class StringUtils
{
    public static function contains($haystack, $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}