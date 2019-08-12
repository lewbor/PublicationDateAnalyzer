<?php


namespace App\Analytics\Analyzer;


use App\Entity\Article;
use Doctrine\ORM\QueryBuilder;

interface AnalyzerInterface
{
    public function getName(): string;

    public function limitArticles(QueryBuilder $qb): void;

    public function datesDiff(Article $article): int;
}