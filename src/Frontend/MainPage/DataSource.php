<?php


namespace App\Frontend\MainPage;


use App\Entity\Journal;
use App\Entity\JournalImpact\JournalJcr2Impact;
use Doctrine\ORM\QueryBuilder;
use PaLabs\DatagridBundle\DataSource\DataSourceSettings;
use PaLabs\DatagridBundle\DataSource\Doctrine\DoctrineDataSource;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\Type\StringFilter;
use PaLabs\DatagridBundle\DataSource\Filter\FilterBuilder;
use PaLabs\DatagridBundle\DataSource\Order\OrderItem;
use PaLabs\DatagridBundle\DataSource\Order\SortBuilder;
use PaLabs\DatagridBundle\Grid\GridContext;
use PaLabs\DatagridBundle\Grid\GridParameters;

class DataSource extends DoctrineDataSource
{

    protected function configureSorting(SortBuilder $builder, GridParameters $parameters): void
    {
        $builder
            ->add('entity.name', 'Название')
            ->add('stat.publisher', 'Издатель')
            ->add('stat.articlesCount', 'Статей')
            ->add('stat.wos_articles', 'Статей (Web of knowledge)')
            ->add('journalImpact2', 'Импакт-фактор JCR 2-летний');
    }

    protected function configureFilters(FilterBuilder $builder, GridParameters $parameters): void
    {
        $builder
            ->add('name', StringFilter::class, [
                'label' => 'Название',
                'default' => true
            ])
            ->add('publisher', StringFilter::class, [
                'label' => 'Издатель',
                'default' => true
            ], null, ['field' => 'stat.publisher']);
    }

    protected function createQuery(GridContext $context): QueryBuilder
    {
        $lastImpactYear = (int)$this->em->createQueryBuilder()
            ->select('MAX(entity.year)')
            ->from(JournalJcr2Impact::class, 'entity')
            ->getQuery()
            ->getSingleScalarResult();

        $qb = $this->em->createQueryBuilder()
            ->select('entity', 'stat')
            ->from(Journal::class, 'entity')
            ->leftJoin('entity.stat', 'stat');

        if ($lastImpactYear > 0) {
            $qb->addSelect(sprintf('(%s) as journalImpact2',
                $this->em->createQueryBuilder()
                    ->select('journal_jcr2impact.value')
                    ->from(JournalJcr2Impact::class, 'journal_jcr2impact')
                    ->andWhere('journal_jcr2impact.journal = entity')
                    ->andWhere('journal_jcr2impact.year = :jcr2impact_year')
                    ->getDQL()))
                ->setParameter('jcr2impact_year', $lastImpactYear);
        }
        return $qb;
    }

    public function defaultSettings(GridParameters $parameters): DataSourceSettings
    {
        return (parent::defaultSettings($parameters))
            ->setOrder([new OrderItem('entity.name', OrderItem::ASC)]);
    }
}