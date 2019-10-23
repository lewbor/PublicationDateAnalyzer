<?php


namespace App\Lib\Selenium;


use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

trait WosCommonActionsTrait
{
    private function switchLanguageToEnglish(RemoteWebDriver $driver)
    {
        $rightMenu = $driver->findElements(WebDriverBy::cssSelector('ul.userCabinet.nav-list li.nav-item'));
        $rightMenuLanguage = $rightMenu[count($rightMenu) - 1];

        $languageMenuLink = $rightMenuLanguage->findElement(WebDriverBy::cssSelector('a.nav-link'));
        $currentLanguage = $languageMenuLink->getText();
        if ($currentLanguage != 'English') {
            $languageMenuLink->click();
            $rootElem = $driver->findElement(WebDriverBy::cssSelector('ul.userCabinet.nav-list'));
            $englishLanguageLink = $rootElem->findElement(WebDriverBy::linkText('English'));
            $englishLanguageLink->click();
            $this->waitPageStartLoading();
        }
    }

    private function waitPageStartLoading()
    {
        sleep(1);
    }
}