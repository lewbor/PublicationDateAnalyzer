<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;
use App\Entity\ArticlePublisherData;

trait ProcessorTrait
{
    private function createPublisherData(Article $article): ArticlePublisherData
    {
        if($article->getPublisherData() !== null) {
            return $article->getPublisherData();
        } else {
            return (new ArticlePublisherData())
                ->setArticle($article);
        }
    }
}