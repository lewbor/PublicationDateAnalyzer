<?php


namespace App\Lib\Utils;


final class PathUtils
{
    public static function projectDir(): string {
        return __DIR__ . '/../../..';
    }
}