<?php


namespace App\Command\Crossref\DomainMerge;


use App\Command\SeleniumTrait;
use App\Entity\Article;
use App\Entity\ArticleUrl;
use App\Entity\ArticleUrlDomain;
use App\Entity\QueueItem;
use App\Lib\QueueManager;
use App\Lib\Selenium\BrowserContext;
use App\Lib\Selenium\DeferredContext;
use App\Lib\Selenium\SeleniumFirefoxTrait;
use App\Lib\Selenium\SeleniumWebdriverTrait;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class DomainMergeScrapCommand extends Command
{
    use SeleniumTrait;
    use SeleniumFirefoxTrait;
    use SeleniumWebdriverTrait;

    protected EntityManagerInterface $em;
    protected QueueManager $queueManager;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager)
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->fs = new Filesystem();
        $this->queueManager = $queueManager;
    }

    protected function configure()
    {
        $this->setName('crossref.domains.scrap');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        foreach ($this->queueManager->singleIterator(DomainMergeQueueCommand::QUEUE_NAME) as $queueItem) {
            try {
                $this->processDomain($queueItem->getData());
            } catch(\Exception $e){
                $this->logger->error($e->getMessage());
            }finally {
                $this->em->clear();
                /** @var QueueItem $queueItem */
                $queueItem = $this->em->getRepository(QueueItem::class)->find($queueItem->getId());
                $this->queueManager->acknowledge($queueItem);
            }
        }
    }

    private function processDomain(array $domainData): void
    {
        /** @var ArticleUrlDomain $domain */
        $domain = $this->em->getRepository(ArticleUrlDomain::class)
            ->find($domainData['id']);
        if ($domain === null) {
            throw new Exception();
        }

        $domainCount = DeferredContext::run(function (DeferredContext $deferred) use ($domain, $domainData) {
            $this->runEnv($deferred);

            $browserContext = new BrowserContext();
            $this->initFirefoxEnv($deferred, $browserContext, '/data/soft/firefox/63.0.3/firefox');

            $driver = $this->createDriver($browserContext, "eager");
            $deferred->defer(function () use ($driver) {
                $driver->close();
            });

            /** @var Article[] $articles */
            $articles = $this->em->createQueryBuilder()
                ->select('entity')
                ->from(Article::class, 'entity')
                ->join('entity.url', 'url')
                ->andWhere('url.domain = :domain')
                ->setParameter('domain', $domain)
                ->groupBy('entity.year')
                ->orderBy('entity.year', 'desc')
                ->setMaxResults(5)
                ->getQuery()
                ->getResult();
            if (count($articles) === 0) {
                throw new Exception(sprintf('No articles for domain %d', $domain->getId()));
            }


            $domainCount = [];
            foreach ($articles as $article) {
                $this->logger->info(sprintf('Process domain=%s, doi=%s, year=%d',
                    $domain->getDomain(),
                    $article->getDoi(),
                    $article->getYear()
                ));

                $url = sprintf('https://doi.org/%s', $article->getDoi());
                $driver->get($url);
                sleep(2);

                $currentUrl = $driver->getCurrentURL();
                $domainStr = parse_url($currentUrl, PHP_URL_HOST);
                $domainStr = trim(strtolower($domainStr));
                if (isset($domainCount[$domainStr])) {
                    $domainCount[$domainStr]++;
                } else {
                    $domainCount[$domainStr] = 1;
                }
                $this->logger->info(sprintf('%s - current domain %s', $domain->getDomain(), $domainStr));
            }

            return $domainCount;
        });

        if (count($domainCount) === 1) {
            $domains = array_keys($domainCount);
            $newDomain = $domains[count($domains) - 1];

            if ($domain->getDomain() !== $newDomain) {
                $this->logger->info(sprintf('Replace domain %s to %s', $domain->getDomain(), $newDomain));
                $this->replaceDomain($domain, $newDomain);
            } else {
                $this->logger->info(sprintf('Domain %s - will save', $domain->getDomain()));
            }
        } else {
            $this->logger->warning(sprintf('Domain %s - found %d domains: %s',
                $domain->getDomain(),
                count($domainCount),
                implode(',', array_keys($domainCount))
            ));
        }

    }

    private function replaceDomain(ArticleUrlDomain $domain, string $newDomain): void
    {
        $newDomainEntity = $this->em->getRepository(ArticleUrlDomain::class)
            ->findOneBy(['domain' => $newDomain]);
        if ($newDomainEntity === null) {
            $newDomainEntity = (new ArticleUrlDomain())
                ->setDomain($newDomain);
            $this->em->persist($newDomainEntity);
            $this->em->flush();
        }

        $this->em->transactional(function () use ($newDomainEntity, $domain, $newDomain) {
            $updatedRecords = $this->em->createQueryBuilder()
                ->update(ArticleUrl::class, 'entity')
                ->set('entity.domain', ':newDomain')
                ->andWhere('entity.domain = :oldDomain')
                ->setParameter('newDomain', $newDomainEntity)
                ->setParameter('oldDomain', $domain)
                ->getQuery()
                ->execute();

            $this->em->remove($domain);
            $this->em->flush();

            $this->logger->info(sprintf('Updated %d articles', $updatedRecords));
        });

    }

}