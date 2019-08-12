<?php


namespace App\Analytics\Analyzer;


use App\Entity\Article;
use Doctrine\ORM\QueryBuilder;
use LogicException;

class AcceptedPublishedAnalyzer implements AnalyzerInterface
{
    use AnalyzerTrait;

    public function getName(): string
    {
        return 'Accepted_Published';
    }

    public function limitArticles(QueryBuilder $qb): void
    {
        $qb
            ->andWhere('entity.publisherAccepted IS NOT NULL')
            ->andWhere('entity.publisherAvailableOnline IS NOT NULL OR entity.publisherAvailablePrint IS NOT NULL');
    }

    public function datesDiff(Article $article): int
    {
        return $this->fromDateToPublished($article->getPublisherAccepted(), $article);
    }
}