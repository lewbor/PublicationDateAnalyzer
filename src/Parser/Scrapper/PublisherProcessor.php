<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;

interface PublisherProcessor
{

    public function name(): string;

    public function publisherNames(): array;

    public function process(Article $article): int;
}