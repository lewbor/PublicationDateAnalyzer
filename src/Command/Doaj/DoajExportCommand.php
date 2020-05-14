<?php


namespace App\Command\Doaj;


use App\Entity\Journal\JournalDoaj;
use App\Lib\CsvWriter;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoajExportCommand extends Command
{

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('doaj.export');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $iterator = DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity')
                ->from(JournalDoaj::class, 'entity')
        );

        $writer = new CsvWriter('php://stdout');
        $writer->open();

        /** @var JournalDoaj $journal */
        foreach ($iterator as $journal) {
            $data = $journal->getData();

            $row = [
                'title' => $data['title'],
                'publisher' => $data['publisher'] ?? '',
                'provider' => $data['provider'] ?? '',
                'country' => $data['country'],
                'publication_time' => $data['publication_time'],
                'oa_start' => $data['oa_start']['year'],
                'language' => implode(',', $data['language']),
                'apc' => isset($data['apc']) ?
                    $data['apc']['average_price'] . ' ' .$data['apc']['currency']
                    : '',
                'homepage' => $this->findHomePage($data),
                'subjects' => isset($data['subject']) ?
                    implode(',', array_map(fn($sub) => $sub['term'], $data['subject']))
                    : ''
            ];

            $writer->write($row);
            $this->em->clear();
        }

        $writer->close();

        return 0;
    }

    private function findHomePage(array $data): string
    {
        foreach ($data['link'] as $link) {
            if ($link['type'] === 'homepage') {
                return $link['url'];
            }
        }
        return '';
    }


}