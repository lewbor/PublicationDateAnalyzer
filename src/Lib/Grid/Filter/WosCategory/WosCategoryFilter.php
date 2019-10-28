<?php


namespace App\Lib\Grid\Filter\WosCategory;


use App\Entity\Jcr\JournalWosCategory;
use Doctrine\ORM\QueryBuilder;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\DoctrineFilterInterface;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\FilterHelper;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\Type\EntityFilter;
use PaLabs\DatagridBundle\DataSource\Filter\FilterFormProvider;
use PaLabs\DatagridBundle\DataSource\Filter\Form\Entity\EntityFilterData;
use PaLabs\DatagridBundle\DataSource\Filter\Form\Entity\EntityFilterForm;
use PaLabs\DatagridBundle\DataSource\Filter\InvalidFilterDataException;

class WosCategoryFilter implements FilterFormProvider, DoctrineFilterInterface
{

    public function formType(): string
    {
        return EntityFilterForm::class;
    }

    public function formOptions(): array
    {
        return [
            'entity_form' => WosCategorySearchForm::class,
        ];
    }

    public function apply(QueryBuilder $qb, string $name, $criteria, array $options = [])
    {
        if (!$criteria instanceof EntityFilterData) {
            throw new InvalidFilterDataException(EntityFilterData::class, $criteria);
        }
        if (!$criteria->isEnabled()) {
            return;
        }

        $operator = $this->operator($criteria->getOperator());
        $entityAlias = FilterHelper::entityAlias($options);

        $qb->andWhere(sprintf('%s %s (%s)', $entityAlias, $operator,
            $qb->getEntityManager()->createQueryBuilder()
                ->select("wos_category_journal")
                ->from(JournalWosCategory::class, "journal_wos_category")
                ->join('journal_wos_category.journal', 'wos_category_journal')
                ->andWhere("journal_wos_category.category = :wos_category")
                ->getDQL()))
            ->setParameter('wos_category', $criteria->getValue());
    }

    private function operator(string $operator)
    {
        switch ($operator) {
            case EntityFilter::OPERATOR_EQUALS:
                return 'IN';
                break;
            case EntityFilter::OPERATOR_NOT_EQUALS:
                return 'NOT IN';
                break;
            default:
                throw new \Exception(sprintf("Unknown organisation filter operator: %s", $operator));
        }
    }
}