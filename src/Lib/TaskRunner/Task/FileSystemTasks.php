<?php


namespace App\Lib\TaskRunner\Task;


use App\Lib\TaskRunner\Logger\NoOpTaskLogger;
use App\Lib\TaskRunner\Logger\TaskLogger;
use Symfony\Component\Filesystem\Filesystem;

class FileSystemTasks
{
    private $fs;
    private $logger;

    public function __construct(TaskLogger $logger)
    {
        $this->fs = new Filesystem();
        $this->logger = $logger;
    }


    public function mkDir($dir)
    {
        $this->logger->logMessage(sprintf('<info>$ mkdir %s</info>', $dir));
        $this->fs->mkdir($dir);
    }

}