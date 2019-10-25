<?php


namespace App\Analytics\Analyzer;


use App\Entity\Article;
use Doctrine\ORM\QueryBuilder;

class ReceivedPublishedAnalyzer implements AnalyzerInterface
{
    use AnalyzerTrait;

    public function getName(): string
    {
        return 'Received_Published';
    }

    public function limitArticles(QueryBuilder $qb): QueryBuilder
    {
        $qb
            ->andWhere('publisherData.publisherReceived IS NOT NULL')
            ->andWhere('publisherData.publisherAvailableOnline IS NOT NULL OR publisherData.publisherAvailablePrint IS NOT NULL');
        return $qb;
    }

    public function datesDiff(Article $article): int
    {
        return $this->fromDateToPublished($article->getPublisherData()->getPublisherReceived(), $article);
    }
}