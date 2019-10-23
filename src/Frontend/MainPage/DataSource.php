<?php


namespace App\Frontend\MainPage;


use App\Entity\Journal;
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
            ->add('stat.articlesCount', 'Статей');
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
        return $this->em->createQueryBuilder()
            ->select('entity', 'stat')
            ->from(Journal::class, 'entity')
            ->leftJoin('entity.stat', 'stat');
    }

    public function defaultSettings(GridParameters $parameters): DataSourceSettings
    {
        return (parent::defaultSettings($parameters))
            ->setOrder([new OrderItem('entity.name', OrderItem::ASC)]);
    }
}