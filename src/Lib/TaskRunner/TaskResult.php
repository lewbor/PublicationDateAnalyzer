<?php


namespace App\Lib\TaskRunner;


class TaskResult
{
    public $exitCode;
    public $output;
    public $context;

    public function __construct($exitCode, $output = '', $context = null) {
        $this->exitCode = $exitCode;
        $this->output = $output;
        $this->context = $context;
    }
}