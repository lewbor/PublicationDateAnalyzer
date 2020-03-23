<?php


namespace App\Parser\Publisher;


use App\Entity\Article;
use Campo\UserAgent;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

class ScrapperUtils
{

    public static function createClient(): Client
    {
        return new Client([
            'cookies' => true,
            'allow_redirects' => true,
            'verify' => false,
            'headers' => [
                'User-Agent' => UserAgent::random([
                    'os_type' => 'Windows',
                    'device_type' => 'Desktop'
                ])
            ],
        ]);
    }

    public static function validateUrl(Article $article, LoggerInterface $logger): bool {
        if ($article->getUrl() === null) {
            $logger->error(sprintf('Article %d - url not exist', $article->getId()));
            return false;
        }

        $url = $article->getUrl()->getUrl();
        if (empty($url)) {
            $logger->error(sprintf('Article %d - url is empty', $article->getId()));
            return false;
        }

        return true;
    }
}