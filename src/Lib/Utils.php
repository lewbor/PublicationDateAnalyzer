<?php


namespace App\Lib;


class Utils
{
    public static function fixEncoding($line)
    {
        //reject overly long 2 byte sequences, as well as characters above U+10000 and replace with ?
        $line = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
            '|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '?', $line);

        //reject overly long 3 byte sequences and UTF-16 surrogates and replace with ?
        $line = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]' .
            '|\xED[\xA0-\xBF][\x80-\xBF]/S', '?', $line);

        // replace bom
        $line = str_replace("\xEF\xBB\xBF",'',$line);
        return $line;
    }
}