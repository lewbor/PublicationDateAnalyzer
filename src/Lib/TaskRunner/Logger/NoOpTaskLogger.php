<?php


namespace App\Lib\TaskRunner\Logger;


use App\Lib\TaskRunner\Logger\TaskLogger;

class NoOpTaskLogger implements TaskLogger
{

    public function logMessage($message)
    {

    }

    public function logOutput($type, $message)
    {

    }
}