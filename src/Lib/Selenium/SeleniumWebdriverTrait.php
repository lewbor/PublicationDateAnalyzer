<?php


namespace App\Lib\Selenium;


use Facebook\WebDriver\Firefox\FirefoxDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverDimension;
use Psr\Log\LoggerInterface;

trait SeleniumWebdriverTrait
{

    /** @var LoggerInterface */
    protected $logger;

    private function createDriver(BrowserContext $browserContext,
                                  ?string $pageLoadStrategy = null,
                                  $connectionTimeout = 30 * 1000,
                                  $requestTimeout = 30 * 10000): RemoteWebDriver {

        $host = 'http://localhost:4444/wd/hub';
        $capabilities = DesiredCapabilities::firefox();
        $capabilities->setCapability(FirefoxDriver::PROFILE, base64_encode(file_get_contents($browserContext->profilePath)));
        $firefoxBin = getenv('FIREFOX_BIN');
        if (empty($firefoxBin)) {
            $browserSettings = $browserContext->browserSettings;
            if (isset($browserSettings['firefoxBin'])) {
                $firefoxBin = $browserSettings['firefoxBin'];
            }
        }
        if (!empty($firefoxBin)) {
            $capabilities->setCapability('firefox_binary', $firefoxBin);
        }
        $capabilities->setCapability('acceptSslCerts', true);
        if($pageLoadStrategy !== null) {
            $capabilities->setCapability("pageLoadStrategy", $pageLoadStrategy);
        }

        $driver = RemoteWebDriver::create($host, $capabilities, $connectionTimeout, $requestTimeout);
        $driver->manage()->window()->setSize(new WebDriverDimension(1920, 1080));
        return $driver;
    }
}