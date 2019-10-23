<?php


namespace App\Lib\TaskRunner;


class Utils
{
    public static function testConnection($host, $port, $tryCount = 10, $sleepInterval = 1)
    {
        for($i = 0; $i < $tryCount; $i++) {
            $fp = @fsockopen($host, $port, $errno, $errstr, 1);
            if($fp !== false) {
                @fclose($fp);
                return true;
            }
            sleep($sleepInterval);
        }
        return false;
    }
}