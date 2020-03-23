<?php


namespace App\Parser\Publisher;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;

trait ProcessorTrait
{
    private function createPublisherData(Article $article, array $data, int $scrapResult): ArticlePublisherData
    {
        if($article->getPublisherData() !== null) {
            $publisherData = $article->getPublisherData();
        } else {
            $publisherData = (new ArticlePublisherData())
                ->setArticle($article);
        }
        $publisherData
            ->setData($data)
            ->setScrapResult($scrapResult)
            ->setScrappedAt(new \DateTime());
        return $publisherData;
    }
}