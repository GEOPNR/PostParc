<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class CityType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
                ->add('insee', null, [
                    'label' => 'City.field.insee',
                    'required' => true,
                ])
                ->add('name', null, [
                    'label' => 'City.field.name',
                    'required' => true,
                ])
                ->add('department', null, [
                    'label' => 'City.field.department',
                    'attr' => ['data-help' => 'ex: ISERE'],
                    'required' => true,
                ])
                ->add('country', null, [
                    'label' => 'City.field.country',
                    'required' => true,
                ])
                ->add('zipCode', null, [
                    'label' => 'City.field.zipCode',
                    'required' => true,
                ])
                ->add('territories', Select2EntityType::class, [
                    'multiple' => true,
                    'remote_route' => 'autocomplete_territory',
                    'class' => 'PostparcBundle:Territory',
                    'label' => 'City.field.territories',
                    'primary_key' => 'id',
                    'text_property' => 'name',
                    'minimum_input_length' => 2,
                    'scroll' => true,
                    'page_limit' => 30,
                    'allow_clear' => true,
                    'delay' => 250,
                    'cache' => true,
                    'cache_timeout' => 60000, // if 'cache' is true
                    'language' => 'fr',
                    'placeholder' => 'actions.selectOneOrManyTerritories',
                    'by_reference' => false,
                  ])
        //->add('isActive', null, array('label' => 'City.field.isActive'))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\City',
        ]);
    }
}
