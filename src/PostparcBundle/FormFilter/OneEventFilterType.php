<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OneEventFilterType extends AbstractType {

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('is_present', CheckboxType::class, [
                    'label' => "Event.present",
                    'required' => false
                ])
                ->add('is_missing', CheckboxType::class, [
                    'label' => "Event.missing",
                    'required' => false,
                    'attr' => ['class' => 'ml-20']

                ])
                ->add('is_represent', CheckboxType::class, [
                    'label' => "Event.represent",
                    'required' => false,
                    'attr' => ['class' => 'ml-20']
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'data_class' => null,
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return 'one_event_filter';
    }
}
