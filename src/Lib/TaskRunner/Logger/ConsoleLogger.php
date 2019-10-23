<?php


namespace App\Lib\TaskRunner\Logger;


use App\Lib\TaskRunner\Logger\TaskLogger;
use Symfony\Component\Console\Formatter\OutputFormatter;

class ConsoleLogger implements TaskLogger
{
    protected $formatter;
    protected $logMessages;
    protected $logOutput;

    public function __construct($logMessages = true, $logOutput = true)
    {
        $this->logMessages = $logMessages;
        $this->logOutput = $logOutput;
        $this->formatter = new OutputFormatter(true);
    }

    public function logMessage($message)
    {
        if ($this->logMessages) {
            echo $this->formatter->format($message);
        }
    }

    public function logOutput($type, $message)
    {
        if ($this->logOutput) {
            echo $message;
        }
    }
}