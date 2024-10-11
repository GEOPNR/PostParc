<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\EntityRepository;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class CityFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('insee', null, ['label' => 'City.field.insee', 'required' => false])
            ->add('zipCode', null, ['label' => 'City.field.zipCode', 'required' => false])
            ->add('name', null, ['label' => 'City.field.name', 'required' => false])
            ->add('department', null, ['label' => 'City.field.department', 'required' => false])
//            ->add('territories', EntityType::class, array(
//              'class' => 'PostparcBundle:Territory',
//              'query_builder' => function (EntityRepository $er) {
//                  return $er->createQueryBuilder('t')
//                        ->orderBy('t.root, t.lft', 'ASC');
//              },
//              'label' => 'Territoire',
//              'required' => false,
//            ))
            ->add('territories', Select2EntityType::class, [
              'multiple' => false,
              'remote_route' => 'autocomplete_territory',
              'class' => 'PostparcBundle:Territory',
              'label' => 'Territory.label',
              'primary_key' => 'id',
              'text_property' => 'name',
              'minimum_input_length' => 2,
              'scroll' => true,
              'page_limit' => 30,
              'allow_clear' => true,
              'delay' => 250,
              'cache' => true,
              'cache_timeout' => 60000, // if 'cache' is true
              'language' => 'fr',
              'placeholder' => 'actions.selectTerritory',
              'required' => false,

            ])
    //->add('isActive', null, array('label' => 'City.field.isActive', 'required' => false))
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => 'PostparcBundle\Entity\City',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'city_filter';
    }
}
