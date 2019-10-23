<?php


namespace App\Lib\TaskRunner\Logger;


class CompositeLogger implements TaskLogger
{
    /** @var TaskLogger[] */
    private $loggers;

    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    public function logMessage($message)
    {
        foreach ($this->loggers as $logger) {
            $logger->logMessage($message);
        }
    }

    public function logOutput($type, $message)
    {
        foreach ($this->loggers as $logger) {
            $logger->logOutput($type, $message);
        }
    }
}