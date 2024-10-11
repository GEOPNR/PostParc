<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\Container;

class TerritoryType extends AbstractType
{
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'Territory.field.name'])
            ->add('parent', null, [
                'label' => 'Territory.field.parent',
                'attr' => [
                    'class' => 'territory-select2-autocomplete',
                ],
            ])
            ->add('territoryType', null, [
                'label' => 'Territory.field.territoryType',
                'attr' => [
                    'class' => 'territoryType-select2-autocomplete',
                ],
            ])
//            ->add('isShared', null, array('label' => 'genericFields.isShared'))
//            ->add('isEditableByOtherEntities', null, array('label' => 'genericFields.isEditableByOtherEntities'))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\Territory',
        ]);
    }
}
