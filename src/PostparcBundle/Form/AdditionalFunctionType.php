<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdditionalFunctionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('slug')
            ->add('name', null, ['label' => 'AdditionalFunction.field.name'])
            ->add('womenName', null, ['label' => 'AdditionalFunction.field.womenName'])
            // ->add('created', 'datetime')
            // ->add('updated', 'datetime')
            // ->add('contentChangedBy')
            // ->add('createdBy')
            // ->add('updatedBy')
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\AdditionalFunction',
        ]);
    }
}
