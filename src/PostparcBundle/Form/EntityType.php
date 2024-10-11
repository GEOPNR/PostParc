<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EntityType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'Entity.field.name'])
            ->add('coordinate', null, [
                'label' => 'genericFields.coordinate',
                'attr' => [
                    'data-help' => 'format : "lat,lng", ex: "45.179225,5.724737"<br/> <a href="https://www.latlong.net/convert-address-to-lat-long.html">www.latlong.net</a>',
                    ],
                ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\Entity',
            'cascade_validation' => true,
        ]);
    }
}
