<?php


namespace App\Lib\Selenium;


class BrowserContext
{
    public $downloadDir;
    public $profilePath;
    public $browserSettings = [];
    // default connection timeout in firefox
    public $browserConnectionTimeout = 90;
}
