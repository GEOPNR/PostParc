<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonFunctionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'label' => 'PersonFunction.field.name',
                'required' => true,
            ])
            ->add(
                'womenName',
                null,
                [
                'label' => 'PersonFunction.field.womenName',
                'required' => true,
                ]
            )
            ->add(
                'menParticle',
                null,
                [
                'label' => 'PersonFunction.field.menParticle',
                'required' => true,
                ]
            )
            ->add(
                'womenParticle',
                null,
                [
                'label' => 'PersonFunction.field.womenParticle',
                'required' => true,
                ]
            )
            ->add('notPrintOnCoordinate', null, [
                'label' => 'PersonFunction.field.notPrintOnCoordinate',
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\PersonFunction',
        ]);
    }
}
