<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\Common\Persistence\ObjectManager;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class CoordinateType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('addressLine1', TextType::class, ['label' => 'Coordinate.field.addressLine1', 'required' => false, 'attr' => ['rows' => '4', 'cols' => '30', 'size' => '250']])
            ->add('addressLine2', TextType::class, ['label' => 'Coordinate.field.addressLine2', 'required' => false, 'attr' => ['rows' => '4', 'cols' => '30', 'size' => '250']])
            ->add('addressLine3', TextType::class, ['label' => 'Coordinate.field.addressLine3', 'required' => false, 'attr' => ['rows' => '4', 'cols' => '30', 'size' => '250']])
            ->add('cedex', null, ['label' => 'Coordinate.field.cedex'])
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
              'placeholder' => 'Coordinate.actions.select_one_city',
              'attr' => ['class' => 'city-select2'],
                    // 'object_manager' => $objectManager, // inject a custom object / entity manager
            ])
            ->add('phoneCode', null, [
                'label' => 'Coordinate.field.phoneCode',
                'data' => '+33'
                ])
            ->add('phone', null, ['label' => 'Coordinate.field.phone'])
            ->add('mobilePhone', null, ['label' => 'Coordinate.field.mobilePhone'])
            ->add('fax', null, ['label' => 'Coordinate.field.fax'])
            ->add('webSite', null, ['label' => 'Coordinate.field.webSite'])
            ->add('email', EmailFormType::class, ['label' => 'Coordinate.field.email', 'required' => false])
            ->add('facebookAccount', null, ['label' => 'Coordinate.field.facebookAccount', 'required' => false])
            ->add('twitterAccount', null, ['label' => 'Coordinate.field.twitterAccount', 'required' => false])
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
         'data_class' => 'PostparcBundle\Entity\Coordinate',
         'cascade_validation' => true,
         'personnalFieldsRestriction' => [],
        ]);
    }
}
