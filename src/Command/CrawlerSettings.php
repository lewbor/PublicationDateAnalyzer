<?php


namespace App\Command;


class CrawlerSettings
{
    const BASE_URL = 'http://apps.webofknowledge.com';
    const PUBLICATIONS_IN_QUERY = 500;
    const QUERIES_PER_SESSION = 5;
}