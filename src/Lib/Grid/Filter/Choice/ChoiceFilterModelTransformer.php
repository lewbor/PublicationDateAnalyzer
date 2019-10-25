<?php


namespace App\Lib\Grid\Filter\Choice;


use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ChoiceFilterModelTransformer implements DataTransformerInterface
{
    public function transform($value)
    {
        if ($value == null) {
            return null;
        }
        if (!$value instanceof ChoiceFilterData) {
            throw new TransformationFailedException();
        }

        return $value->toUrlParams();
    }

    public function reverseTransform($value)
    {
        if ($value === null) {
            return null;
        }
        if (empty($value[ChoiceFilterForm::OPERATOR_FIELD])) {
            throw new TransformationFailedException();
        }
        return new ChoiceFilterData($value[ChoiceFilterForm::OPERATOR_FIELD], $value[ChoiceFilterForm::VALUE_FIELD] ?? []);
    }
}