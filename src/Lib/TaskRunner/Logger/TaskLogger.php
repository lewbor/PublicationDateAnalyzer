<?php


namespace App\Lib\TaskRunner\Logger;


interface TaskLogger
{
    public function logMessage($message);
    public function logOutput($type, $message);
}