<?php


namespace App\Lib\Selenium;


class Path
{
    public static function join()
    {
        $paths = [];

        foreach (func_get_args() as $arg) {
            if ($arg !== '') {
                $paths[] = $arg;
            }
        }

        return preg_replace('#' . DIRECTORY_SEPARATOR . '+#', DIRECTORY_SEPARATOR, join(DIRECTORY_SEPARATOR, $paths));
    }
}