<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class TerritoryTypeFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'TerritoryType.field.name', 'required' => false])

            ->add('updatedBy', EntityType::class, [
                'label' => 'updatedBy',
                'class' => 'PostparcBundle:User',
                'required' => false,
                'choice_label' => function ($user) {
                    return $user->getDisplayName();
                },
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\TerritoryType',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'territoryType_filter';
    }
}
