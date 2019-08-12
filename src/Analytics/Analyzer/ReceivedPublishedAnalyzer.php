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

    public function limitArticles(QueryBuilder $qb): void
    {
        $qb
            ->andWhere('entity.publisherReceived IS NOT NULL')
            ->andWhere('entity.publisherAvailableOnline IS NOT NULL OR entity.publisherAvailablePrint IS NOT NULL');
    }

    public function datesDiff(Article $article): int
    {
        return $this->fromDateToPublished($article->getPublisherReceived(), $article);
    }
}