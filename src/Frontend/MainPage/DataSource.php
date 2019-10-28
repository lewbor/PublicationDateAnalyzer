<?php


namespace App\Frontend\MainPage;


use App\Entity\Jcr\JournalJcrQuartile;
use App\Entity\Journal\Journal;
use App\Entity\Jcr\JournalJcr2Impact;
use App\Entity\Journal\JournalStat;
use App\Lib\Grid\Filter\Choice\ChoiceFilter;
use App\Lib\Grid\Filter\JcrQuartile\JcrQuartileFilter;
use App\Lib\Grid\Filter\WosCategory\WosCategoryFilter;
use App\Lib\JcrYearLocator;
use Doctrine\ORM\QueryBuilder;
use PaLabs\DatagridBundle\DataSource\DataSourceSettings;
use PaLabs\DatagridBundle\DataSource\Doctrine\DoctrineDataSource;
use PaLabs\DatagridBundle\DataSource\Doctrine\DoctrineDataSourceServices;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\Type\IntegerFilter;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\Type\IntegerHavingFilter;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\Type\StringFilter;
use PaLabs\DatagridBundle\DataSource\Filter\FilterBuilder;
use PaLabs\DatagridBundle\DataSource\Order\OrderItem;
use PaLabs\DatagridBundle\DataSource\Order\SortBuilder;
use PaLabs\DatagridBundle\Grid\GridContext;
use PaLabs\DatagridBundle\Grid\GridParameters;

class DataSource extends DoctrineDataSource
{
    protected $jcrYearLocator;

    public function __construct(
        DoctrineDataSourceServices $services,
        JcrYearLocator $jcrYearLocator)
    {
        parent::__construct($services);
        $this->jcrYearLocator = $jcrYearLocator;
    }

    protected function configureSorting(SortBuilder $builder, GridParameters $parameters): void
    {
        $builder
            ->add('entity.name', 'Название')
            ->add('stat.publisher', 'Издатель')
            ->add('stat.articlesCount', 'Статей')
            ->add('stat.wos_articles', 'Статей (Web of knowledge)')
            ->add('jcrImpact2', 'Импакт-фактор JCR 2-летний')
            ->add('jcrQuartile', 'Квартиль JCR')
            ->add('entity.id', 'ID');
    }

    protected function configureFilters(FilterBuilder $builder, GridParameters $parameters): void
    {
        $builder
            ->add('name', StringFilter::class, [
                'label' => 'Название',
                'default' => true
            ])
            ->add('publisher', ChoiceFilter::class, [
                'label' => 'Издатель',
                'default' => true,
                'entity_options' => [
                    'choices' => $this->publisherChoices()
                ]
            ], null, ['field' => 'stat.publisher'])
            ->add('wosCategory', WosCategoryFilter::class, [
                'label' => 'Категория Web of science'
            ])
            ->add('jcrQuartile', JcrQuartileFilter::class, [
                'label' => 'Квартиль JCR',
                'default' => true
            ]);
    }

    protected function createQuery(GridContext $context): QueryBuilder
    {
        $lastImpactYear =$this->jcrYearLocator->latestYear();

        $qb = $this->em->createQueryBuilder()
            ->select('entity', 'stat', 'wosCategories')
            ->from(Journal::class, 'entity')
            ->leftJoin('entity.stat', 'stat')
            ->leftJoin('entity.wosCategories', 'wosCategories');

        if ($lastImpactYear > 0) {
            $qb->addSelect(sprintf('(%s) as jcrImpact2',
                $this->em->createQueryBuilder()
                    ->select('journal_jcr2impact.value')
                    ->from(JournalJcr2Impact::class, 'journal_jcr2impact')
                    ->andWhere('journal_jcr2impact.journal = entity')
                    ->andWhere('journal_jcr2impact.year = :jcr2impact_year')
                    ->getDQL()))
                ->setParameter('jcr2impact_year', $lastImpactYear);

            $qb->addSelect(sprintf('(%s) as jcrQuartile', $this->quartileExpression()))
                ->setParameter('jcr_quartile_year', $lastImpactYear);
        }
        return $qb;
    }

    private function quartileExpression(): string
    {
        return $this->em->createQueryBuilder()
            ->select('MIN(journal_jcr_quartile.quartile)')
            ->from(JournalJcrQuartile::class, 'journal_jcr_quartile')
            ->andWhere('journal_jcr_quartile.journal = entity')
            ->andWhere('journal_jcr_quartile.year = :jcr_quartile_year')
            ->getDQL();
    }

    public function defaultSettings(GridParameters $parameters): DataSourceSettings
    {
        return (parent::defaultSettings($parameters))
            ->setOrder([new OrderItem('entity.name', OrderItem::ASC)]);
    }

    private function publisherChoices(): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('entity.publisher', 'COUNT(entity.id) AS journalCount')
            ->from(JournalStat::class, 'entity')
            ->groupBy('entity.publisher')
            ->orderBy('journalCount', 'desc')
            ->getQuery()
            ->getResult();

        $choices = [];
        foreach ($rows as $row) {
            $label = sprintf('%s (%s)', $row['publisher'], $row['journalCount']);
            $choices[$label] = $row['publisher'];
        }
        return $choices;
    }
}