<?php


namespace App\Command\Wos\JournalCrawler;


use App\Command\CrawlerSettings;
use App\Command\SeleniumTrait;
use App\Entity\Journal\Journal;
use App\Lib\QueueManager;
use App\Lib\Selenium\BrowserContext;
use App\Lib\Selenium\DeferredContext;
use App\Lib\Selenium\Path;
use App\Lib\Selenium\SeleniumFirefoxTrait;
use App\Lib\Selenium\SeleniumWebdriverTrait;
use App\Lib\Selenium\UrlWaiter;
use App\Lib\Selenium\WosCommonActionsTrait;
use App\Lib\Utils\PathUtils;
use Doctrine\ORM\EntityManagerInterface;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class WosJournalCrawlerCommand extends Command
{
    use SeleniumTrait;
    use SeleniumFirefoxTrait;
    use SeleniumWebdriverTrait;
    use WosCommonActionsTrait;

    protected $logger;
    protected $em;
    protected $queueManager;
    protected $fs;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        QueueManager $queueManager
    )
    {
        parent::__construct();
        $this->em = $em;
        $this->logger = $logger;
        $this->queueManager = $queueManager;
        $this->fs = new Filesystem();
    }

    protected function configure()
    {
        $this->setName('wos.journal.craw');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Journal $journal */
        foreach ($this->queueManager->singleIterator(WosJournalQueerCommand::QUEUE_NAME) as $idx => $queueItem) {
            $data = $queueItem->getData();
            $journal = $this->em->getRepository(Journal::class)->find($data['id']);
            if ($journal === null) {
                $this->logger->error(sprintf('Journal id=%d does not exist', $data['id']));
                $this->queueManager->acknowledge($queueItem);
                continue;
            }

            $this->logger->info(sprintf('Start processing journal %d (%s)', $journal->getId(), $journal->getName()));
            $this->processJournal($journal);
            $this->em->clear();
            $this->logger->info(sprintf('End processing journal %d (%s)', $journal->getId(), $journal->getName()));
            $this->queueManager->acknowledge($queueItem);
            sleep(10);
        }
    }

    private function processJournal(Journal $journal): void
    {
        $saveDir = sprintf('%s/data/wos/%d', PathUtils::projectDir(), $journal->getId());
        if ($this->fs->exists($saveDir)) {
            $this->fs->remove($saveDir);
            $this->logger->info(sprintf('Removed dir %s', $saveDir));
        }
        $this->fs->mkdir($saveDir);
        $this->logger->info(sprintf('Created dir %s', $saveDir));

        DeferredContext::run(function (DeferredContext $deferred) use ($saveDir, $journal) {
            $this->runEnv($deferred);

            $browserContext = new BrowserContext();
            $this->initFirefoxEnv($deferred, $browserContext, '/data/soft/firefox/63.0.3/firefox');

            $driver = $this->createDriver($browserContext, "eager");
            $deferred->defer(function () use ($driver) {
                $driver->close();
            });

            $this->logger->info(sprintf('Visit %s', CrawlerSettings::BASE_URL));
            $driver->get(CrawlerSettings::BASE_URL);
            $this->logger->info("Switching language to English");
            $this->switchLanguageToEnglish($driver);

            $query = sprintf('SO="%s"', $journal->getName());
            $this->search($driver, $query, 'WOS_GeneralSearch_input.do', 'WOS_AdvancedSearch_input.do');

            $searchResultItemsCount = $this->searchResultItemsCount($driver, 1);
            $this->logger->info(sprintf("Found %d results", $searchResultItemsCount));

            if ($searchResultItemsCount <= 0) {
                return;
            }

            if ($searchResultItemsCount > 100000) {
                $this->goToSearchResults($driver, 1);

                $years = $this->buildYearFacet($driver);
                asort($years);
                $yearSlices = $this->buildYearSlices($years);

                foreach ($yearSlices as $sliceNumber => $slice) {
                    $query = sprintf('SO="%s" AND (%s)',
                        $journal->getName(),
                        implode(' OR ', array_map(function ($year) {
                            return sprintf('PY=%d', $year);
                        }, $slice))
                    );
                    $driver->get(CrawlerSettings::BASE_URL);
                    $this->search($driver, $query, 'WOS_GeneralSearch_input.do', 'WOS_AdvancedSearch_input.do');
                    $searchResultItemsCount = $this->searchResultItemsCount($driver, 1 + $sliceNumber + 1);
                    $this->logger->info(sprintf("Found %d results", $searchResultItemsCount));

                    $this->goToSearchResults($driver, 1 + $sliceNumber + 1);
                    $this->saveAll($driver, $saveDir, $browserContext->downloadDir, $sliceNumber, $searchResultItemsCount);

                }
            } else {
                $this->goToSearchResults($driver, 1);
                $this->saveAll($driver, $saveDir, $browserContext->downloadDir, '', $searchResultItemsCount);
            }
        });
    }

    private function buildYearSlices(array $years): array
    {
        $slices = [];

        $currentSlice = [];
        $currentSum = 0;
        foreach ($years as $year => $articlesCount) {
            if ($currentSum + $articlesCount > 100000) {
                $slices[] = $currentSlice;
                $currentSlice = [];
                $currentSum = 0;
            }
            $currentSlice[] = $year;
            $currentSum += $articlesCount;
        }
        if (count($currentSlice) > 0) {
            $slices[] = $currentSlice;
        }

        return $slices;
    }

    private function buildYearFacet(RemoteWebDriver $driver): array
    {
        $years = [];
        $driver->findElement(WebDriverBy::cssSelector('a#PublicationYear'))->click();

        $yearElements = $driver->findElements(WebDriverBy::cssSelector('form#refine_more_form tr#PublicationYear_raMore_tr td.refineItem label'));
        foreach ($yearElements as $yearElement) {
            $labelValue = $yearElement->getText();
            $labelParts = explode(' ', trim($labelValue));
            if (count($labelParts) !== 2) {
                $this->logger->error(sprintf('Year label format error: %s', $labelValue));
                continue;
            }
            $yearCount = (int)trim(str_replace(['(', ')', ','], '', $labelParts[1]));
            $years[(int)trim($labelParts[0])] = $yearCount;
        }
        return $years;
    }

    private function saveAll(RemoteWebDriver $driver, string $saveDir,
                             string $downloadDir, string $exportFilePrefix, int $searchResultItemsCount)
    {
        $recordsPerSave = 500;
        $saveCount = (int)ceil($searchResultItemsCount / $recordsPerSave);

        for ($i = 0; $i < $saveCount; $i++) {
            $startNumber = $i * $recordsPerSave + 1;
            $endNumber = min($startNumber + $recordsPerSave - 1, $searchResultItemsCount);
            $saveFileName = Path::join($saveDir, sprintf('%s_%s_%04d_%04d.txt', 'wos_abstract', $exportFilePrefix, $startNumber, $endNumber));

            $this->logger->info("Saving results");
            $this->selectSaveOption($driver);
            $this->fillSaveRecordsForm($driver, $startNumber, $endNumber);

            $downloadFileName = Path::join($downloadDir, 'savedrecs.txt');
            $this->logger->info(sprintf("Waiting for results to download, %s", $downloadFileName));
            $isFileSaved = $this->waitForFileDownload($downloadFileName);
            if ($isFileSaved) {
                $this->fs->rename($downloadFileName, $saveFileName, true);
                $this->logger->info(sprintf("Saved results to %s", $saveFileName));
            } else {
                $this->logger->error(sprintf('Filed to save file %s', $downloadFileName));
                $this->fs->remove($downloadFileName);
            }

            $driver->navigate()->refresh();
            sleep(1);
        }
    }

    protected function waitForFileDownload(string $filename, int $tryCount = 60, int $delay = 1): bool
    {
        for ($currentTry = 0; $currentTry < $tryCount; $currentTry++) {
            clearstatcache();
            if (!file_exists($filename)) {
                sleep($delay);
                continue;
            }
            $size = filesize($filename);
            if ($size > 0) {
                return true;
            }
            sleep($delay);
        }
        return false;
    }

    private function fillSaveRecordsForm(RemoteWebDriver $driver, $startRecordNumber, $endRecordNumber)
    {
        $saveForm = $driver->findElement(WebDriverBy::cssSelector('form.quick-output-form'));

        $recordsRangeRadio = $saveForm->findElement(WebDriverBy::cssSelector('#numberOfRecordsRange'));
        $recordsRangeRadio->click();

        $from = $saveForm->findElement(WebDriverBy::name("markFrom"));
        $from->click();
        $from->clear();
        $from->sendKeys($startRecordNumber);

        $to = $saveForm->findElement(WebDriverBy::name("markTo"));
        $to->click();
        $to->clear();
        $to->sendKeys($endRecordNumber);

        // $fullRecordValue = "HIGHLY_CITED HOT_PAPER OPEN_ACCESS PMID USAGEIND AUTHORSIDENTIFIERS ACCESSION_NUM FUNDING SUBJECT_CATEGORY JCR_CATEGORY LANG IDS PAGEC SABBR CITREFC ISSN PUBINFO KEYWORDS CITTIMES ADDRS CONFERENCE_SPONSORS DOCTYPE ABSTRACT CONFERENCE_INFO SOURCE TITLE AUTHORS";
        $this->selectSelect2OptionByValue($driver, 'bib_fields', 'Full Record');

        $this->selectSelect2OptionById($driver, 'saveOptions', 'tabWinUTF8');

        $sendButton = $saveForm->findElement(WebDriverBy::cssSelector("form.quick-output-form #exportButton"));
        $sendButton->click();
    }

    private function selectSelect2OptionById(RemoteWebDriver $driver, string $selectId, string $itemId)
    {
        $selectRoot = $driver->findElement(WebDriverBy::cssSelector(sprintf("select[id='%s'] ~ span.select2", $selectId)));

        $arrow = $selectRoot->findElement(WebDriverBy::cssSelector('span.select2-selection__arrow b'));
        $arrow->click();

        $menuItem = $driver->findElement(WebDriverBy::cssSelector(sprintf("span.select2-container--open span.select2-results li[id*='%s']", $itemId)));
        $menuItem->click();
    }

    private function selectSelect2OptionByValue(RemoteWebDriver $driver, string $selectId, string $value)
    {
        $selectRoot = $driver->findElement(WebDriverBy::cssSelector(sprintf("select[id='%s'] ~ span.select2", $selectId)));

        $arrow = $selectRoot->findElement(WebDriverBy::cssSelector('span.select2-selection__arrow b'));
        $arrow->click();

        $menuItem = $driver->findElement(WebDriverBy::xpath(sprintf('//span[contains(@class, "select2-container--open")]//span[contains(@class, "select2-results")]//li[contains(text(), "%s")]', $value)));
        $menuItem->click();
    }

    private function selectSaveOption(RemoteWebDriver $driver)
    {
        $driver->findElement(WebDriverBy::id('exportTypeName'))->click();

        try {
            $driver->findElement(WebDriverBy::cssSelector('form.quick-output-form'));
        } catch (NoSuchElementException $e) {
            $driver->findElement(WebDriverBy::cssSelector('ul#saveToMenu li.subnav-item a[name="Export to Other File Formats"]'))->click();
        }
    }

    protected function goToSearchResults(RemoteWebDriver $driver, $executedQueriesInSession)
    {
        $this->logger->info("Going to search results");
        $searchResultRow = $this->findSearchResultRow($driver, $executedQueriesInSession);
        $searchResultLink = $searchResultRow->findElement(WebDriverBy::cssSelector('a[title="Click to view the results"]'));
        $searchResultLink->click();
        (new UrlWaiter($driver))->forUrlPart('summary.do');
    }

    protected function search(RemoteWebDriver $driver, $query, string $baseSearchUrl = 'WOS_GeneralSearch_input.do', string $advancedSearchUrl = 'WOS_AdvancedSearch_input.do')
    {
        (new UrlWaiter($driver))->forUrlPart($baseSearchUrl);
        $this->waitJsExecution();

        $this->logger->info("Going to Advanced search page");
        $searchBlock = $driver->findElement(WebDriverBy::cssSelector('div.block-search'));
        $advanceSearchLink = $searchBlock->findElement(WebDriverBy::linkText("Advanced Search"));
        $advanceSearchLink->click();
        (new UrlWaiter($driver))->forUrlPart($advancedSearchUrl);

        $this->logger->info("Filling search form");
        $driver->findElement(WebDriverBy::cssSelector('textarea[id="value(input1)"]'));
        $js = sprintf('document.getElementById("%s").value = "%s"; return true', 'value(input1)', addslashes($query));
        $driver->executeScript($js);
        $this->waitJsExecution();

        $this->logger->info("Searching");
        $searchButton = $driver->findElement(WebDriverBy::cssSelector('#search-button'));
        $searchButton->click();
        (new UrlWaiter($driver))->forUrlPart('goToPageLoc=SearchHistoryTableBanner');
    }

    protected function searchResultItemsCount(RemoteWebDriver $driver, $executedQueriesInSession)
    {
        $searchResultRow = $this->findSearchResultRow($driver, $executedQueriesInSession);
        $resultCount = $searchResultRow->findElement(WebDriverBy::cssSelector('div.historyResults'))->getText();
        $resultCount = str_replace(',', '', $resultCount);
        $resultCount = trim($resultCount);
        return (int)$resultCount;
    }

    private function findSearchResultRow(RemoteWebDriver $driver, $executedQueriesInSession)
    {
        $searchResultRow = $driver->findElement(WebDriverBy::cssSelector(sprintf('tr[id=set_%d_row]', $executedQueriesInSession)));
        return $searchResultRow;
    }


    private function waitJsExecution()
    {
        sleep(1);
    }


}