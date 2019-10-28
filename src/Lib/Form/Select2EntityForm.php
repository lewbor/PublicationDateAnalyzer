<?php


namespace App\Lib\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Select2EntityForm extends AbstractType
{
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr'] = array_merge($view->vars['attr'], [
            'data-widget' => 'select2',
            'data-language' => $this->requestStack->getMasterRequest()->getLocale(),
            'data-multiple' => json_encode($options['multiple']),
            'data-allow-clear' => json_encode(!$options['required']),
        ]);

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'multiple' => false
            ]);
    }

    public function getParent()
    {
        return EntityType::class;
    }


}