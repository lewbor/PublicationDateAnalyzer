<?php


namespace App\Lib\Iterator;


final class IteratorUtils
{
    public static function itemCount(iterable $iterator): int {
        $itemCount = 0;
        foreach ($iterator as $item) {
            $itemCount++;
        }
        return $itemCount;
    }
}