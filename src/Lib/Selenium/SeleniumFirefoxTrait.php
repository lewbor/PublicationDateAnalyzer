<?php


namespace App\Lib\Selenium;


use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

trait SeleniumFirefoxTrait
{

    /** @var  Filesystem */
    protected $fs;
    /** @var  LoggerInterface */
    protected $logger;


    private function initFirefoxEnv(DeferredContext $deferred, BrowserContext $browserContext, string $browserBin): void {
        $tmpDir = Path::join(sys_get_temp_dir(), uniqid('sciact_'));
        $this->fs->mkdir($tmpDir);
        $deferred->defer(function () use ($tmpDir) {
            $this->fs->remove($tmpDir);
        });

        $browserContext->browserSettings = ['firefoxBin' => $browserBin];

        $this->createFirefoxEnvironment($browserContext, $tmpDir);
    }

    private function createFirefoxEnvironment(BrowserContext $browserContext, $tmpDir)
    {
        $this->setupDirectoryStructure($browserContext, $tmpDir);
        $this->createBrowserProfile($browserContext, $tmpDir);
        return $browserContext;
    }

    private function setupDirectoryStructure(BrowserContext $browserContext, $tmpDir): void
    {
        $downloadDir = $tmpDir . '/downloads';
        $this->fs->mkdir($downloadDir);

        $browserContext->downloadDir = $downloadDir;
    }

    private function createBrowserProfile(BrowserContext $browserContext, string $tmpDir): void
    {
        $firefoxConfig = [
            'browser.download.folderList' => 2,
            'browser.download.manager.showWhenStarting' => false,
            'browser.download.dir' => $browserContext->downloadDir,
            'browser.helperApps.neverAsk.saveToDisk' => implode(',', [
                'application/xml',
                'text/plain',
                'text/csv',
                'multipart/x-zip',
                'application/zip',
                'application/x-zip-compressed',
                'application/x-compressed',
                'application/octet-stream'
            ])
        ];
        $httpProxy = getenv('HTTP_PROXY');
        if (!empty($httpProxy)) {
            $proxyUrlParts = parse_url($httpProxy);
            if ($proxyUrlParts['scheme'] != 'socks5') {
                throw new Exception(sprintf('Currently only socks5 proxy supports, HTTP_PROXY=%s', $httpProxy));
            }
            $proxyHost = $proxyUrlParts['host'];
            $proxyPort = $proxyUrlParts['port'];
            $firefoxConfig = array_merge($firefoxConfig, [
                "network.proxy.type" => 1,
                "network.proxy.socks" => $proxyHost,
                "network.proxy.socks_port" => $proxyPort,
            ]);
            $this->logger->info(sprintf('Using proxy HTTP_PROXY=%s', $httpProxy));
        }


        $prefsJsContent = '';
        foreach ($firefoxConfig as $paramName => $value) {
            if (is_int($value)) {
                $prefsJsContent .= sprintf("user_pref(\"%s\", %d);\n", $paramName, $value);
            } else {
                $prefsJsContent .= sprintf("user_pref(\"%s\", \"%s\");\n", $paramName, $value);
            }

        }
        $this->fs->dumpFile($tmpDir . '/prefs.js', $prefsJsContent);
        $profileFile = $tmpDir . '/profile.zip';
        exec(sprintf('zip -j %s %s', $profileFile, $tmpDir . '/prefs.js'));
        $this->fs->remove($tmpDir . '/prefs.js');

        $browserContext->profilePath = $profileFile;
    }
}