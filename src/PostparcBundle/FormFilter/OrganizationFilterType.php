<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class OrganizationFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'Organization.field.name', 'required' => false])
            ->add('abbreviation', null, ['label' => 'Organization.field.abbreviation', 'required' => false])
            ->add('siret', null, ['label' => 'Organization.field.siret', 'required' => false])    
            ->add('updatedBy', EntityType::class, [
              'label' => 'updatedBy',
              'class' => 'PostparcBundle:User',
              'required' => false,
              'choice_label' => function ($user) {
                  return $user->getDisplayName();
              },
            ])
            ->add('organizationType', Select2EntityType::class, [
              'multiple' => false,
              'label' => 'Organization.field.organizationType',
              'remote_route' => 'autocomplete_organizationType',
              'class' => 'PostparcBundle:OrganizationType',
              'primary_key' => 'id',
              'text_property' => 'name',
              'minimum_input_length' => 2,
              'page_limit' => 30,
              'scroll' => true,
              'allow_clear' => true,
              'delay' => 250,
              'cache' => true,
              'cache_timeout' => 60000, // if 'cache' is true
              'width' => '100%',
              'language' => 'fr',
              'placeholder' => 'actions.selectOrganizationType',
                    // 'object_manager' => $objectManager, // inject a custom object / entity manager
            ])
            ->add('tags', Select2EntityType::class, [
              'multiple' => true,
              'remote_route' => 'autocomplete_tag',
              'class' => 'PostparcBundle:Tag',
              'label' => 'genericFields.tags',
              'primary_key' => 'id',
              'text_property' => 'name',
              'minimum_input_length' => 2,
              'page_limit' => 30,
              'scroll' => true,
              'allow_clear' => true,
              'delay' => 250,
              'cache' => true,
              'cache_timeout' => 60000, // if 'cache' is true
              'language' => 'fr',
              'placeholder' => 'actions.selectOneOrManyTags',
              'width' => '100%',
                    // 'object_manager' => $objectManager, // inject a custom object / entity manager
            ])
            ->add('city', Select2EntityType::class, [
              'multiple' => false,
              'remote_route' => 'autocomplete_city',
              'class' => 'PostparcBundle:City',
              'label' => 'Coordinate.field.city',
              'primary_key' => 'id',
              'text_property' => 'name',
              'minimum_input_length' => 2,
              'page_limit' => 30,
              'scroll' => true,
              'allow_clear' => true,
              'delay' => 250,
              'cache' => true,
              'cache_timeout' => 60000, // if 'cache' is true
              'language' => 'fr',
              'placeholder' => 'actions.selectCity',
              'width' => '100%',
                    // 'object_manager' => $objectManager, // inject a custom object / entity manager
            ])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => 'PostparcBundle\Entity\Organization',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'organization_filter';
    }
}
