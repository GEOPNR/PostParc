<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class OrganizationTypeType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'OrganizationType.field.name'])
            ->add(
                'parent',
                EntityType::class,
                [
                        'required' => false,
                        'label' => 'OrganizationType.field.parent',
                        'class' => 'PostparcBundle:OrganizationType',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('ot')
                                ->orderBy('ot.slug', 'ASC');
                        },
                    ]
            )
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\OrganizationType',
            'cascade_validation' => true,
        ]);
    }
}
