<?php


namespace App\Lib\Utils;


final class StringUtils
{
    public static function contains(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }

    public static function startsWith(string $haystack, string $needle, bool $strictCase = true)
    {
        if ($strictCase) {
            return strpos($haystack, $needle, 0) === 0;
        }
        return stripos($haystack, $needle, 0) === 0;
    }

    public static function startsWithAny(string $haystack, array $needles, bool $strictCase = true): bool
    {
        foreach ($needles as $needle) {
            if (self::startsWith($haystack, $needle, $strictCase)) {
                return true;
            }
        }
        return false;
    }
}