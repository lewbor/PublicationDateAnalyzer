<?php


namespace App\Analytics\Analyzer;


use App\Entity\Article;
use DateTime;
use LogicException;

trait AnalyzerTrait
{

    private function dateDiffs(DateTime $start, DateTime $end): int {
        return (int)$start->diff($end)->format('%r%a');
    }

    private function fromDateToPublished(DateTime $fromDate, Article $article) {
        if($article->getPublisherData() === null) {
            throw new LogicException(sprintf('Article has no publisher data, id=%d', $article->getId()));
        }
        $publisherData = $article->getPublisherData();
        if($publisherData->getPublisherAvailableOnline() !== null && $publisherData->getPublisherAvailablePrint() !== null) {
            $diff = $this->dateDiffs($publisherData->getPublisherAvailablePrint(), $publisherData->getPublisherAvailableOnline());
            if($diff > 365 * 3) {
                return $this->dateDiffs($fromDate, $publisherData->getPublisherAvailablePrint());
            } else {
                return $this->dateDiffs($fromDate, $publisherData->getPublisherAvailableOnline());
            }
        }

        if($publisherData->getPublisherAvailableOnline() === null && $publisherData->getPublisherAvailablePrint() !== null) {
            return $this->dateDiffs($fromDate, $publisherData->getPublisherAvailablePrint());
        }
        if($publisherData->getPublisherAvailableOnline() !== null && $publisherData->getPublisherAvailablePrint() === null) {
            return $this->dateDiffs($fromDate, $publisherData->getPublisherAvailableOnline());
        }

        throw new LogicException();
    }
}