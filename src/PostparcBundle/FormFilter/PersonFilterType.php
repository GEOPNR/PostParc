<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class PersonFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'Person.field.name', 'required' => false])
            ->add('city', Select2EntityType::class, [
              'multiple' => false,
              'label' => 'Coordinate.field.city',
              'remote_route' => 'autocomplete_city_all',
              'class' => 'PostparcBundle:City',
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
              'width' => '100%',
              'placeholder' => 'Coordinate.actions.select_one_city',
              'mapped' => false,
                    // 'object_manager' => $objectManager, // inject a custom object / entity manager
            ])
            ->add('profession', Select2EntityType::class, [
              'multiple' => false,
              'remote_route' => 'autocomplete_profession',
              'class' => 'PostparcBundle:Profession',
              'label' => 'Person.field.profession',
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
              'placeholder' => 'actions.selectProfession',
              'width' => '100%',
                    // 'object_manager' => $objectManager, // inject a custom object / entity manager
            ])
            ->add('updatedBy', EntityType::class, [
              'label' => 'updatedBy',
              'class' => 'PostparcBundle:User',
              'required' => false,
              'choice_label' => function ($user) {
                  return $user->getDisplayName();
              },
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
        ;

        // fields restriction for the current role user
        $personnalFieldsRestriction = $options['personnalFieldsRestriction'];
        if (is_array($personnalFieldsRestriction)) {
            foreach ($personnalFieldsRestriction as $field) {
                $builder->remove($field);
            }
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => 'PostparcBundle\Entity\Person',
          'personnalFieldsRestriction' => [],
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'person_filter';
    }
}
