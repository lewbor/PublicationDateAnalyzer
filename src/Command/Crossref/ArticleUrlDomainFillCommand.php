<?php


namespace App\Command\Crossref;


use App\Entity\ArticleUrl;
use App\Entity\ArticleUrlDomain;
use App\Lib\Iterator\DoctrineIterator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ArticleUrlDomainFillCommand extends Command
{
    const BATCH_SIZE = 10000;

    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('crossref.domains_update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var ArticleUrl[] $iterator */
        $iterator = DoctrineIterator::batchIdIterator($this->em->createQueryBuilder()
            ->select('entity')
            ->from(ArticleUrl::class, 'entity')
            ->andWhere('entity.url IS NOT NULL')
            ->andWhere('entity.domain IS NULL')
            , 'entity', self::BATCH_SIZE);

        foreach ($iterator as $idx => $batch) {
            $domains = $this->getDomains();

            /** @var ArticleUrl $articleUrl */
            foreach ($batch as $articleUrl) {
                $url = trim($articleUrl->getUrl());
                if (empty($url)) {
                    continue;
                }

                $domainStr = parse_url($url, PHP_URL_HOST);
                if (empty($domainStr)) {
                    $this->logger->error(sprintf('ArticleUrl(%s) incorrect url %s', $articleUrl->getId(), $url));
                    continue;
                }
                $domainStr = strtolower($domainStr);

                if (isset($domains[$domainStr])) {
                    $domainEntity = $domains[$domainStr];
                } else {
                    $domainEntity = (new ArticleUrlDomain())
                        ->setDomain($domainStr);
                    $this->em->persist($domainEntity);
                    $domains[$domainStr] = $domainEntity;
                }
                $articleUrl->setDomain($domainEntity);
                $this->em->persist($articleUrl);

            }
            $this->em->flush();
            $this->em->clear();
            $this->logger->info(sprintf('Processed %s records', number_format(($idx + 1) * self::BATCH_SIZE)));
        }
    }

    private function getDomains(): array
    {
        /** @var ArticleUrlDomain[] $domains */
        $domains = $this->em->getRepository(ArticleUrlDomain::class)->findAll();

        $result = [];
        foreach ($domains as $domain) {
            $result[$domain->getDomain()] = $domain;
        }

        return $result;
    }
}