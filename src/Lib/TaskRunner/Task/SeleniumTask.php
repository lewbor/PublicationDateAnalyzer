<?php


namespace App\Lib\TaskRunner\Task;


use App\Lib\TaskRunner\Logger\NoOpTaskLogger;
use App\Lib\TaskRunner\Logger\TaskLogger;
use App\Lib\TaskRunner\Task\Exec\ExecBackgroundTrait;
use App\Lib\TaskRunner\Task\Exec\ExecOneTrait;
use App\Lib\TaskRunner\TaskException;
use App\Lib\TaskRunner\TaskResult;
use App\Lib\TaskRunner\Utils;
use function GuzzleHttp\default_ca_bundle;
use Symfony\Component\Process\Process;

class SeleniumTask
{
    use ExecBackgroundTrait;
    use CommandArguments;

    protected $executable;
    protected $driverPath;
    protected $driverType;
    protected $logFile = null;

    public function __construct(string $seleniumExecutable, string $driverPath, string $driverType = 'gecko')
    {
        $this->executable = $seleniumExecutable;
        $this->driverPath = $driverPath;
        $this->driverType = $driverType;
    }

    public function run(TaskLogger $logger)
    {
        switch($this->driverType) {
            case 'gecko':
                $arguments = "-Dwebdriver.gecko.driver={$this->driverPath}";
                break;
            case 'chrome':
                $arguments = "-Dwebdriver.chrome.driver={$this->driverPath}";
                break;
            default:
                throw new \Exception(sprintf("Unknown driver type: %s", $this->driverPath));
        }

        $arguments .= " -jar {$this->executable} -enablePassThrough false";
        if($this->logFile !== null) {
            $arguments .= sprintf(' >%s 2>&1', $this->logFile);
        } else {
            $arguments .= ' >/dev/null 2>&1';
        }

        $cmd = "java {$arguments}";
        $this->executeCommandInBackground($cmd, $logger);

        if (!Utils::testConnection('localhost', 4444)) {
            throw new TaskException('Cant connect to selenium host');
        }
    }

    public function logFile($logFile) {
        $this->logFile = $logFile;
        return $this;
    }

}