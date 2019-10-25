<?php


namespace App\Analytics;


use App\Entity\Journal\JournalAnalytics;
use App\Lib\CsvWriter;
use Doctrine\ORM\EntityManagerInterface;

class AnalyticsSaver
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function save() {
        $savePath = __DIR__ . '/../../data/stat/journals.csv';
        if (file_exists($savePath)) {
            unlink($savePath);
        }
        $writer = new CsvWriter($savePath, "\t");
        $writer->open();

        /** @var JournalAnalytics $analytics */
        foreach($this->journalAnalyticsIterator() as $analytics) {

            $row = [
                'ID' => $analytics->getJournal()->getId(),
                'Name' => trim($analytics->getJournal()->getName()),
                'Publisher' => trim($analytics->getJournal()->getCrossrefData()['publisher']) ?? ''
            ];

            foreach ($analytics->getAnalytics() as $periodName => $periodStat) {
                foreach(['articles'] as $statKey) {
                    $row[$periodName . ' - ' . $statKey] = $periodStat[$statKey];
                }
                foreach ($periodStat['analyzers'] as $statName => $statData) {
                    $row[sprintf('%s (%s)', $periodName, $statName)] = implode(' / ', [
                        $statData['count'],
                        sprintf('%d (%d)', $statData['min'], $statData['min_count']),
                        sprintf('%d (%d)', $statData['max'], $statData['max_count']),
                        $statData['avg'],
                        sprintf('%d (%d)', $statData['median'], $statData['median_count']),
                        count($statData['quartiles']) === 0 ? '' : sprintf('%d - %d - %d - %d',
                            $statData['quartiles'][0]['value'],
                            $statData['quartiles'][1]['value'],
                            $statData['quartiles'][2]['value'],
                            $statData['quartiles'][3]['value'])
                    ]);
                }
            }

            $writer->write($row);
        }

        $writer->close();
    }

    private function journalAnalyticsIterator(): iterable
    {
        $iterator = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(JournalAnalytics::class, 'entity')
            ->getQuery()
            ->iterate();
        foreach ($iterator as $item) {
            yield $item[0];
        }
    }
}