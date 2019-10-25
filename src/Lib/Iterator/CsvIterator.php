<?php


namespace App\Lib\Iterator;


use App\Lib\Iterator\FileIterator;
use App\Lib\Utils\Utils;
use Symfony\Component\Form\Util\StringUtil;

class CsvIterator
{

    public static function csv($lineIterator, $delimiter = null, $enclosure = null, $escape = null)
    {
        foreach ($lineIterator as $value) {
            $row = str_getcsv($value[FileIterator::LINE], $delimiter, $enclosure, $escape);
            yield array_replace($value, [FileIterator::LINE => $row]);
        }
    }

    public static function clearedCsv($csvIterator)
    {
        foreach ($csvIterator as $value) {
            $normalizedRow = array_map(function ($item) {
                return StringUtil::fixEncoding(trim($item));
            }, $value[FileIterator::LINE]);
            yield array_replace($value, [FileIterator::LINE => $normalizedRow]);
        }
    }

    public static function trimmedCsv($csvIterator)
    {
        foreach ($csvIterator as $value) {
            $normalizedRow = array_map(function ($item) {
                return trim($item);
            }, $value[FileIterator::LINE]);
            yield array_replace($value, [FileIterator::LINE => $normalizedRow]);
        }
    }
}