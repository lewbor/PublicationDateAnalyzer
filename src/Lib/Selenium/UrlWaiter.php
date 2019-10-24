<?php


namespace App\Lib\Selenium;


use App\Lib\Utils\StringUtils;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class UrlWaiter
{
    const FIND_TIMEOUT = 10 * 1000;

    protected $driver;

    public function __construct(RemoteWebDriver $driver)
    {
        $this->driver = $driver;
    }

    public function forUrlPart(string $urlPart): void
    {
        $this->waitForNonEmptyResult(self::FIND_TIMEOUT,
            function () use ($urlPart) {
                $currentUrl = $this->driver->getCurrentUrl();
                return StringUtils::contains($currentUrl, $urlPart);
            },
            sprintf('Wait for url filed, %s', $urlPart)
        );
    }

    protected function waitForNonEmptyResult(int $timeout, callable $elementSelectorCallback, string $errorMessage = 'Wait timeout reached')
    {
        $start = microtime(true);
        $end = $start + $timeout / 1000.0;

        do {
            $result = $elementSelectorCallback();
            if (!empty($result)) {
                return $result;
            }
            usleep(100000);
        } while (microtime(true) < $end);

        throw new \Exception($errorMessage);
    }
}