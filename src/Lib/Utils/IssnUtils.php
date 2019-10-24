<?php


namespace App\Lib\Utils;


final class IssnUtils
{

    public static function formatIssnWithHyphen(?string $issn): string {
        if($issn === null) {
            return '';
        }

        $strlen = strlen($issn);
        if($strlen < 8 || $strlen > 8) {
            return $issn;
        }

        return sprintf('%s-%s', substr($issn, 0, 4), substr($issn, 4));
    }
}