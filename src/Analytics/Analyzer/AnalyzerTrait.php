<?php


namespace App\Analytics\Analyzer;


use App\Entity\Article;
use DateTime;
use LogicException;

trait AnalyzerTrait
{

    private function dateDiffs(\DateTime $start, \DateTime $end): int {
        return (int)$start->diff($end)->format('%r%a');
    }

    private function fromDateToPublished(DateTime $fromDate, Article $article) {
        if($article->getPublisherAvailableOnline() !== null && $article->getPublisherAvailablePrint() !== null) {
            $diff = $this->dateDiffs($article->getPublisherAvailablePrint(), $article->getPublisherAvailableOnline());
            if($diff > 365 * 3) {
                return $this->dateDiffs($fromDate, $article->getPublisherAvailablePrint());
            } else {
                return $this->dateDiffs($fromDate, $article->getPublisherAvailableOnline());
            }
        }

        if($article->getPublisherAvailableOnline() === null && $article->getPublisherAvailablePrint() !== null) {
            return $this->dateDiffs($fromDate, $article->getPublisherAvailablePrint());
        }
        if($article->getPublisherAvailableOnline() !== null && $article->getPublisherAvailablePrint() === null) {
            return $this->dateDiffs($fromDate, $article->getPublisherAvailableOnline());
        }

        throw new LogicException();
    }
}