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

    public function limitArticles(QueryBuilder $qb): void
    {
        $qb
            ->andWhere('entity.publisherReceived IS NOT NULL')
            ->andWhere('entity.publisherAccepted IS NOT NULL');
    }

    public function datesDiff(Article $article): int
    {
        return $this->dateDiffs($article->getPublisherReceived(), $article->getPublisherAccepted());
    }
}