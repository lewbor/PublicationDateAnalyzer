<?php


namespace App\Parser\Publisher;


use App\Entity\Article;

interface PublisherProcessor
{

    public function name(): string;

    public function scrappingDomains(): array;

    public function queueName(): string;

    public function process(Article $article): void;
}