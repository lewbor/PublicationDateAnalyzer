<?php


namespace App\Command\Stuff;


use App\Entity\Article;
use App\Entity\ArticleUrl;
use App\Entity\Journal\Journal;
use App\Lib\CsvWriter;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JournalDomainListCommand extends Command
{
    private const NAME = 'journal.domains';

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct(self::NAME);
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName(self::NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Journal[] $journalIterator */
        $journalIterator = DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Journal::class, 'entity')
        );

        $writer = new CsvWriter('php://stdout');
        $writer->open();

        foreach ($journalIterator as $journal) {
            $domains = $this->journalDomains($journal);
            foreach ($domains as $domain) {
                $row = [
                    'journal_id' => $journal->getId(),
                    'journal_name' => $journal->getName(),
                    'domain_id' => $domain['id'],
                    'domain_name' => $domain['domain'],
                    'articles' =>  $domain['articles'],
                    'urls' => implode(" ", $domain['urls'])
                ];
                $writer->write($row);
            }
            $this->em->clear();
        }

        $writer->close();


    }

    private function journalDomains(Journal $journal): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('domain.id', 'domain.domain', 'COUNT(entity.id) as articles')
            ->from(Article::class, 'entity')
            ->join('entity.url', 'url')
            ->join('url.domain', 'domain')
            ->andWhere('entity.journal = :journal')
            ->setParameter('journal', $journal)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            fn(array $row) => [
                'id' => $row['id'],
                'domain' => $row['domain'],
                'articles' => $row['articles'],
                'urls' => empty($row['id']) ? [] : $this->urls($row['id'])
            ], $rows);
    }

    private function urls(int $domainId): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('entity.url')
            ->from(ArticleUrl::class, 'entity')
            ->join('entity.domain', 'domain')
            ->andWhere('domain.id = :id')
            ->setParameter('id', $domainId)
            ->getQuery()
            ->setMaxResults(2)
            ->getArrayResult();

        return array_map(
            fn(array $row) => $row['url'],
            $rows
        );
    }

    private function saveData(array $header, array $rows): void
    {
        $fp = fopen('php://stdout', 'w');

        fputcsv($fp, $header);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);
    }
}