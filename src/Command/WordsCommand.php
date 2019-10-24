<?php


namespace App\Command;


use App\Entity\Article;
use App\Entity\Journal;
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
                ->select('entity')
                ->from(Article::class, 'entity')
        );

        $articleCount = 0;

        $names = [];
        /** @var Article $article */
        foreach ($iterator as $article) {
            $articleName = strtolower($article->getName());

            $nameParts = explode(' ', $articleName);
            if (count($nameParts) <= 3) {
                $names[$articleName] = isset($names[$articleName]) ? $names[$articleName] + 1 : 1;
            }

            if(count($names) > 1000) {
                break;
            }
        }

        print_r($names);
    }
}