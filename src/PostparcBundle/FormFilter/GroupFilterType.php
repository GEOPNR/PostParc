<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class GroupFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'Group.field.name', 'required' => false])
            ->add('onlyMyEntityGroups', CheckboxType::class, [
                'label' => 'Group.onlyMyEntityGroups',
                'required' => false,
                'mapped' => false,
                ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\Group',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'group_filter';
    }
}
