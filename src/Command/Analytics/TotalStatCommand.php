<?php


namespace App\Command\Analytics;


use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TotalStatCommand extends Command
{

    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('common_stat');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        echo sprintf("Всего статей: %s\n", $this->format($this->totalArticles()));
        echo sprintf("Имеют дату Received: %s\n", $this->format($this->receivedArticles()));
        echo sprintf("Имеют дату Accepted: %s\n", $this->format($this->acceptedArticles()));
        echo sprintf("Имеют дату Published print (publisher): %s\n", $this->format($this->publishedPrint()));
        echo sprintf("Имеют дату Published online (publisher): %s\n", $this->format($this->publishedOnline()));
        echo sprintf("Имеют дату Published print (crossref): %s\n", $this->format($this->publishedPrintCrossref()));
        echo sprintf("Имеют дату Published online (crossref): %s\n", $this->format($this->publishedOnlineCrossref()));
        echo sprintf("Имеют все даты: %s\n", $this->format($this->allDates()));
        echo sprintf("Имеют все даты (1,2, 3 или 4): %s\n", $this->format($this->allDatesAnyPublished()));

    }

    private function totalArticles(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function acceptedArticles(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.publisherAccepted IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function receivedArticles(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.publisherReceived IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function publishedPrint(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.publisherAvailablePrint IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function publishedOnline(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.publisherAvailableOnline IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function publishedOnlineCrossref(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.publishedOnline IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function publishedPrintCrossref(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.publishedPrint IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function allDates(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.publisherReceived IS NOT NULL')
            ->andWhere('entity.publisherAccepted IS NOT NULL')
            ->andWhere('entity.publisherAvailablePrint IS NOT NULL')
            ->andWhere('entity.publisherAvailableOnline IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function allDatesAnyPublished(): int
    {
        return (int) $this->em->createQueryBuilder()
            ->select('COUNT(entity.id)')
            ->from(Article::class, 'entity')
            ->andWhere('entity.publisherReceived IS NOT NULL')
            ->andWhere('entity.publisherAccepted IS NOT NULL')
            ->andWhere('entity.publisherAvailablePrint IS NOT NULL OR entity.publisherAvailableOnline IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function format(int $number): string
    {
        return number_format($number);
    }
}