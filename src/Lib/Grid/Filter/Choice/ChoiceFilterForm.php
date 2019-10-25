<?php


namespace App\Lib\Grid\Filter\Choice;


use App\Lib\Form\Select2Form;
use PaLabs\DatagridBundle\DataSource\Filter\BaseFilterForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceFilterForm extends AbstractType
{
    const OPERATOR_FIELD = 'o';
    const VALUE_FIELD = 'v';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(self::OPERATOR_FIELD, ChoiceType::class, [
                'choices' => [
                    'Содержится' => ChoiceFilter::OPERATOR_EQUALS,
                    'Не содержится' => ChoiceFilter::OPERATOR_NOT_EQUALS
                ]
            ])
            ->add(self::VALUE_FIELD, Select2Form::class,
                array_merge(['required' => false, 'multiple' => true], $options['entity_options']));

        $builder->addModelTransformer(new ChoiceFilterModelTransformer());
    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'entity_options'
        ]);
    }

    public function getParent()
    {
        return BaseFilterForm::class;
    }


}