<?php


namespace App\Lib\Grid\Filter\JcrQuartile;


use App\Entity\Jcr\JournalJcrQuartile;
use App\Lib\Grid\Filter\Choice\ChoiceFilter;
use App\Lib\Grid\Filter\Choice\ChoiceFilterData;
use App\Lib\JcrYearLocator;
use Doctrine\ORM\QueryBuilder;
use PaLabs\DatagridBundle\DataSource\Filter\InvalidFilterDataException;

class JcrQuartileFilter extends ChoiceFilter
{
    protected $jcrYearLocator;

    public function __construct(JcrYearLocator $jcrYearLocator)
    {
        $this->jcrYearLocator = $jcrYearLocator;
    }

    public function formOptions(): array
    {
        return [
            'entity_options' => [
                'choices' => ['Q1' => 1, 'Q2' => 2, 'Q3' => 3, 'Q4' => 4, 'Нет квартиля' => 0]
            ]
        ];
    }

    public function apply(QueryBuilder $qb, string $name, $criteria, array $options = [])
    {
        if (!$criteria instanceof ChoiceFilterData) {
            throw new InvalidFilterDataException(ChoiceFilterData::class, $criteria);
        }

        if (!$criteria->isEnabled()) {
            return;
        }

        $hasNoQuartileFilter = $this->hasNoQuartileFilter($criteria->getValue());
        $quartiles = $this->quartiles($criteria->getValue());

        $expressions = [];
        foreach ($quartiles as $quartile) {
            $expressions[] = sprintf('(%s) = %d',
                $qb->getEntityManager()->createQueryBuilder()
                    ->select("MIN(journal_wos_quartile_filter_{$quartile}.quartile)")
                    ->from(JournalJcrQuartile::class, "journal_wos_quartile_filter_{$quartile}")
                    ->andWhere("journal_wos_quartile_filter_{$quartile}.journal = entity")
                    ->andWhere(sprintf("journal_wos_quartile_filter_{$quartile}.year = %d", $this->jcrYearLocator->latestYear()))
                    ->getDQL(), $quartile);
        }
        if ($hasNoQuartileFilter) {
            $expressions[] = sprintf('entity NOT IN (%s)',
                $qb->getEntityManager()->createQueryBuilder()
                    ->select("no_quartile_filter_journal")
                    ->from(JournalJcrQuartile::class, "no_quartile_filter")
                    ->join('no_quartile_filter.journal', 'no_quartile_filter_journal')
                    ->andWhere(sprintf("no_quartile_filter.year = %s", $this->jcrYearLocator->latestYear()))
                    ->getDQL());
        }

        $qb->andWhere(implode(' OR ', $expressions));
    }

    private function hasNoQuartileFilter(array $values): bool
    {
        foreach ($values as $value) {
            if ($value === 0) {
                return true;
            }
        }
        return false;
    }

    private function quartiles(array $values): array
    {
        $result = [];

        foreach ($values as $value) {
            if ($value > 0) {
                $result[] = $value;
            }
        }

        return $result;

    }
}