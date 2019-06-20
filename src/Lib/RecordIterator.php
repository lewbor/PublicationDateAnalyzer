<?php


namespace App\Lib;


use App\Lib\FileIterator;
use Exception;

class RecordIterator
{
    public static function record($csvIterator)
    {
        $readHeader = false;
        $header = null;
        foreach ($csvIterator as $value) {
            if (!$readHeader) {
                $header = $value[FileIterator::LINE];
                $readHeader = true;
                continue;
            }
            $record = self::createRecord($header, $value);
            yield $record;
        }
    }

    private static function createRecord($header, $value)
    {
        $row = $value[FileIterator::LINE];

        if (self::recordIsValid($header, $row)) {
            $data = [];
            $headerCount = count($header);
            for ($i = 0; $i < $headerCount; $i++) {
                $data[$header[$i]] = $row[$i];
            }
            return [$data, null];
        }
        $error = new Exception(sprintf("File %s, Line %d: header and row size is not valid, header has %d elements, row - %d",
            $value[FileIterator::FILE], $value[FileIterator::LINE_NUMBER], count($header), count($row)));
        return [null, $error];


    }

    private static function recordIsValid(array $header, array $row)
    {
        $headerCount = count($header);
        $rowCount = count($row);
        if ($headerCount == $rowCount) {
            return true;
        }

        if ($headerCount < $rowCount) {
            for($i = $headerCount; $i < $rowCount; $i++) {
                if(!empty($row[$i])) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }
}