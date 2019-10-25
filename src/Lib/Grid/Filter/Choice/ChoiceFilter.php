<?php


namespace App\Lib\Grid\Filter\Choice;


use Doctrine\ORM\QueryBuilder;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\DoctrineFilterInterface;
use PaLabs\DatagridBundle\DataSource\Doctrine\Filter\FilterHelper;
use PaLabs\DatagridBundle\DataSource\Filter\FilterFormProvider;
use PaLabs\DatagridBundle\DataSource\Filter\InvalidFilterDataException;

class ChoiceFilter implements FilterFormProvider, DoctrineFilterInterface
{
    CONST OPERATOR_EQUALS = 'equals';
    const OPERATOR_NOT_EQUALS = 'not_equals';

    public function formType(): string
    {
        return ChoiceFilterForm::class;
    }

    public function formOptions(): array
    {
        return [];
    }

    public function apply(QueryBuilder $qb, string $name, $criteria, array $options = [])
    {
        if (!$criteria instanceof ChoiceFilterData) {
            throw new InvalidFilterDataException(ChoiceFilterData::class, $criteria);
        }
        if (!$criteria->isEnabled()) {
            return;
        }


        $fieldName = FilterHelper::fieldName($name, $options);
        $parameterName = FilterHelper::parameterName($name, $options);

        switch ($criteria->getOperator()) {
            case self::OPERATOR_EQUALS:
                $qb->andWhere(sprintf('%s IN (:%s)', $fieldName, $parameterName))
                    ->setParameter($parameterName, $criteria->getValue());
                break;
            case self::OPERATOR_NOT_EQUALS:
                $qb->andWhere(sprintf('%s NOT IN (:%s)', $fieldName, $parameterName))
                    ->setParameter($parameterName, $criteria->getValue());
                break;
        }
    }
}