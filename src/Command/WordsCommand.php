<?php


namespace App\Command;


use App\Entity\Article;
use App\Entity\Journal\Journal;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WordsCommand extends Command
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setName('test.words');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $iterator = DoctrineIterator::idIterator(
            $this->em->createQueryBuilder()
                ->select('entity', 'partial publisherData.{id}', 'partial webOfScienceData.{id}', 'partial crossrefData.{id}')
                ->from(Article::class, 'entity')
                ->leftJoin('entity.publisherData', 'publisherData')
                ->leftJoin('entity.webOfScienceData', 'webOfScienceData')
                ->leftJoin('entity.crossrefData', 'crossrefData')
        );

        $names = [];
        /** @var Article $article */
        foreach ($iterator as $article) {
            $articleName = strtolower($article->getName());

            $nameParts = explode(' ', $articleName);
            if (count($nameParts) <= 3) {
                $names[$articleName] = isset($names[$articleName]) ? $names[$articleName] + 1 : 1;
            }

            if (count($names) > 1000) {
                break;
            }
        }

        arsort($names);
        print_r($names);
    }
}