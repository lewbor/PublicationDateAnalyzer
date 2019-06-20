<?php


namespace App\Lib;


class FileIterator
{
    const LINE = 0;
    const LINE_NUMBER = 1;
    const FILE = 2;

    public static function line($file)
    {
        $handle = fopen($file, 'r');
        if (false === $handle) {
            throw new \Exception(sprintf("Cant open file %s", $file));
        }

        $lineNumber = 0;
        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            yield [
                self::LINE => $line,
                self::LINE_NUMBER => $lineNumber,
                self::FILE => $file];
        }

        fclose($handle);
    }

    public static function skipFirstLineIterator($lineIterator, $linesToSkip)
    {
        foreach ($lineIterator as $value) {
            if ($value[self::LINE_NUMBER] <= $linesToSkip) {
                continue;
            }
            yield $value;
        }

    }
}