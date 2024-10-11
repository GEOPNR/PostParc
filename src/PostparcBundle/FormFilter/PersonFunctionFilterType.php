<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonFunctionFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'PersonFunction.field.name', 'required' => false])
            ->add('womenName', null, ['label' => 'PersonFunction.field.womenName', 'required' => false])
            ->add('menParticle', null, ['label' => 'PersonFunction.field.menParticle', 'required' => false])
            ->add('womenParticle', null, ['label' => 'PersonFunction.field.womenParticle', 'required' => false])
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

    /**
     * @return string
     */
    public function getName()
    {
        return 'pf_filter';
    }
}
