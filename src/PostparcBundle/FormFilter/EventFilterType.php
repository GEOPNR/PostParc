<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class EventFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, ['label' => 'genericFields.name', 'required' => false])
            ->add('eventType', Select2EntityType::class, [
              'multiple' => false,
              'remote_route' => 'autocomplete_eventType',
              'class' => 'PostparcBundle:EventType',
              'label' => 'Event.field.eventType',
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
              'placeholder' => 'actions.selectEventType',
              'width' => '100%',
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
            ->add('createdBy', null, ['label' => 'createdBy', 'required' => false])    
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\Event',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'event_filter';
    }
}
