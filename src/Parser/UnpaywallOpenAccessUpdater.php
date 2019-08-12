<?php


namespace App\Parser;


use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class UnpaywallOpenAccessUpdater
{
    protected $em;
    protected $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger
    )
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function run()
    {
        $processedItems = 0;
        foreach ($this->articleIterator() as $article) {
            $this->processArticle($article);
            $this->em->clear();

            $processedItems++;
            if ($processedItems % 50 === 0) {
                $this->logger->info(sprintf('Processed %d articles', $processedItems));
            }
        }
    }

    private function articleIterator()
    {
        $iterator = $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Article::class, 'entity')
            ->andWhere('entity.unpaywallData IS NOT NULL')
            ->getQuery()
            ->iterate();
        foreach ($iterator as $item) {
            yield $item[0];
        }
    }

    private function processArticle(Article $article): void
    {
        $crossrefData = $article->getUnpaywallData();

        if(!isset($crossrefData['is_oa'])) {
            $this->logger->info(sprintf('Article id=%d: openaccess field is not exist', $article->getId()));
            return;
        }
        $isOpenAccess = (bool)$crossrefData['is_oa'];
        if ($article->isOpenAccess() !== $isOpenAccess) {
            $article->setOpenAccess($isOpenAccess);
            $this->em->persist($article);
            $this->em->flush();
        }
    }
}