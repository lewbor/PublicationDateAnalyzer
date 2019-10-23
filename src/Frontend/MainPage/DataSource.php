<?php


namespace App\Frontend\MainPage;


use App\Entity\Journal;
use App\Entity\JournalAnalytics;
use Doctrine\ORM\QueryBuilder;
use PaLabs\DatagridBundle\DataSource\DataSourceConfiguration;
use PaLabs\DatagridBundle\DataSource\DataSourceSettings;
use PaLabs\DatagridBundle\DataSource\Doctrine\DoctrineDataSource;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\Type\StringFilter;
use PaLabs\DatagridBundle\DataSource\Filter\FilterBuilder;
use PaLabs\DatagridBundle\DataSource\Order\OrderItem;
use PaLabs\DatagridBundle\DataSource\Order\SortBuilder;
use PaLabs\DatagridBundle\DataSource\Result\DataSourcePageContext;
use PaLabs\DatagridBundle\Grid\GridContext;
use PaLabs\DatagridBundle\Grid\GridParameters;

class DataSource extends DoctrineDataSource
{

    protected function configureSorting(SortBuilder $builder, GridParameters $parameters): void
    {
        $builder
            ->add('entity.name', 'Название');
    }

    protected function configureFilters(FilterBuilder $builder, GridParameters $parameters): void
    {
        $builder->add('name', StringFilter::class, [
            'label' => 'Название',
            'default' => true
        ]);
    }

    protected function createQuery(GridContext $context): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('entity')
            ->from(Journal::class, 'entity');
    }

    protected function buildPageContext(array $rows, DataSourceConfiguration $configuration, GridContext $context, DataSourcePageContext $pageContext): void
    {
        $ids = array_map(function(Journal $journal){
            return $journal->getId();
        }, $rows);

        $analytics = $this->em->createQueryBuilder()
            ->select('entity', 'journal')
            ->from(JournalAnalytics::class, 'entity')
            ->join('entity.journal', 'journal')
            ->andWhere('journal.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $journalAnalytics = [];
        /** @var JournalAnalytics $entity */
        foreach($analytics as $entity) {
            $journalAnalytics[$entity->getJournal()->getId()] = $entity;
        }

        $pageContext->set(JournalAnalytics::class, $journalAnalytics);
    }

    public function defaultSettings(GridParameters $parameters): DataSourceSettings
    {
        return (parent::defaultSettings($parameters))
            ->setOrder([new OrderItem('entity.name', OrderItem::ASC)]);
    }
}