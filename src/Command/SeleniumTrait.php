<?php


namespace App\Command;


use App\Lib\Selenium\DeferredContext;
use App\Lib\TaskRunner\Logger\ConsoleLogger;
use App\Lib\TaskRunner\Task\SeleniumTask;

trait SeleniumTrait
{
    private function runEnv(DeferredContext $deferred)
    {
        $taskLogger = new ConsoleLogger();
        $seleniumTask = (new SeleniumTask('/data/soft/selenium/selenium-server-standalone-3.8.1.jar',
            '/data/soft/selenium/geckodriver-0.23.0'
        ));

        $seleniumTask->run($taskLogger);
        $deferred->defer(function () use ($seleniumTask, $taskLogger) {
            $seleniumTask->stop($taskLogger);
        });
    }
}