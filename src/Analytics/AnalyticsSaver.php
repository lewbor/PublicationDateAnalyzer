<?php


namespace App\Analytics;


use App\Lib\CsvWriter;

class AnalyticsSaver
{

    public function save(array $analytics) {
        $savePath = __DIR__ . '/../../data/stat/journals.csv';
        if (file_exists($savePath)) {
            unlink($savePath);
        }
        $writer = new CsvWriter($savePath, "\t");
        $writer->open();

        foreach ($analytics as $id => $stat) {
            $row = [];
            foreach (['Name', 'Issn', 'Publisher',] as $valueToCopy) {
                $row[$valueToCopy] = $stat[$valueToCopy];
            }
            foreach ($stat['byPeriods'] as $periodName => $periodStat) {
                foreach(['Articles'] as $statKey) {
                    $row[$periodName . ' - ' . $statKey] = $periodStat[$statKey];
                }
                foreach ($periodStat['analyzers'] as $statName => $statData) {
                    $row[sprintf('%s (%s)', $periodName, $statName)] = implode(' / ', [
                        $statData['count'],
                        $statData['min'],
                        $statData['max'],
                        $statData['avg'],
                        $statData['median'],
                    ]);
                }
            }

            $writer->write($row);
        }

        $writer->close();
    }
}