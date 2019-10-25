<?php


namespace App\Analytics\Analyzer;


use App\Entity\Article;
use Doctrine\ORM\QueryBuilder;

class ReceivedAcceptedAnalyzer implements AnalyzerInterface
{
    use AnalyzerTrait;

    public function getName(): string
    {
        return 'Received_Accepted';
    }

    public function limitArticles(QueryBuilder $qb): QueryBuilder
    {
        $qb
            ->andWhere('publisherData.publisherReceived IS NOT NULL')
            ->andWhere('publisherData.publisherAccepted IS NOT NULL');
        return $qb;
    }

    public function datesDiff(Article $article): int
    {
        return $this->dateDiffs($article->getPublisherData()->getPublisherReceived(), $article->getPublisherData()->getPublisherAccepted());
    }
}