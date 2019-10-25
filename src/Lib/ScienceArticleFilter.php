<?php


namespace App\Lib;


use App\Entity\Article;
use App\Lib\Utils\StringUtils;

class ScienceArticleFilter
{

    public function apply(iterable $iterator): iterable
    {
        $excludedTitles = [
            'errata',
            'erratum',
            'corrigendum',
            'editorial board',
            'contents',
            'preface',
            'author index',
            'subject index',
            'index',
            'calender',
            'events',
            'contents list',
            'newsbrief',
            'contents continued',
            'ifc',
            'calendar',
            'graphical abstract toc',
            'editorial',
            'patent report',
            'ifc editorial board',
            'instructions to authors',
            'meeting report'
        ];
        $prefixes = [
            'erratum to',
            'corrigendum to'
        ];

        /** @var Article $article */
        foreach ($iterator as $article) {
            $lowerCaseName = strtolower($article->getName());
            if(empty($lowerCaseName)) {
                continue;
            }
            if(in_array($lowerCaseName, $excludedTitles)) {
                continue;
            }

            if(StringUtils::startsWithAny($lowerCaseName, $prefixes)) {
                continue;
            }

            yield $article;
        }
    }
}