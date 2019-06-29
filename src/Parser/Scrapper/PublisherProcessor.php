<?php


namespace App\Parser\Scrapper;


use App\Entity\Article;

interface PublisherProcessor
{

    public function publisherName(): string;
    public function process(Article $article): int;
}