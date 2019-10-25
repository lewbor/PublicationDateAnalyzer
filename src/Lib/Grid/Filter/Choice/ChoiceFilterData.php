<?php


namespace App\Lib\Grid\Filter\Choice;


use PaLabs\DatagridBundle\DataSource\Filter\FilterDataInterface;

class ChoiceFilterData implements FilterDataInterface
{
    /** @var  string */
    protected $operator;

    /** @var  array */
    protected $value;

    public function __construct(string $operator, array $value = [])
    {
        $this->operator = $operator;
        $this->value = $value;
    }

    public function isEnabled(): bool {
        return !empty($this->value);
    }

    public function toUrlParams(): array
    {
        if(!$this->isEnabled()) {
            return [];
        }

        return [
            ChoiceFilterForm::OPERATOR_FIELD => $this->getOperator(),
            ChoiceFilterForm::VALUE_FIELD => $this->value
        ];
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getValue(): array
    {
        return $this->value;
    }

}