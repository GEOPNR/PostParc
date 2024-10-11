<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use PostparcBundle\Form\DataTransformer\CityToNumberTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class PersonType extends AbstractType
{
    public $personId;
    private $manager;

    public function __construct(ObjectManager $manager, $personId = null)
    {
        $this->personId = $personId;
        $this->manager = $manager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $user = $options['user'];

        $builder
            ->add('image', FileType::class, [
                'label' => 'Person.field.image',
                'required' => false,
                'data_class' => null,
                'attr' => [
                ],
              ])
            ->add('civility', null, ['label' => 'Person.field.civility', 'required' => true])
            ->add('name', null, ['label' => 'Person.field.name', 'required' => true])
            ->add('firstName', null, ['label' => 'Person.field.firstName', 'required' => true])
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
              'attr' => ['class' => 'ajax-add-new-profession'],
                    // 'object_manager' => $objectManager, // inject a custom object / entity manager
            ])
            ->add('birthDate', BirthdayType::class, [
              'label' => 'Person.field.birthDate',
              'required' => false,
              'widget' => 'single_text',
              'html5' => false,
              'format' => 'dd-MM-yyyy',
              'attr' => ['class' => 'js-datepicker'],
            ])
            ->add('birthLocation', HiddenType::class, [
              'invalid_message' => 'error.notAValidCityNumber',
              'required' => false,
              'label' => 'Person.field.birthLocation',
                    //'attr' => array('class' => 'city-select2-autocomplete-all')
            ])
//            ->add('tags', null, [
//                'label' => 'genericFields.tags',
//                'placeholder' => 'actions.selectOneOrManyTags',
//                'attr' => [
//                  'class' => 'select2 ajax-add-new-tag'
//                ]
//                ])
            ->add('tags', Select2EntityType::class, [
              'multiple' => true,
              'remote_route' => 'autocomplete_tag',
              'class' => 'PostparcBundle:Tag',
              'label' => 'genericFields.tags',
              'primary_key' => 'id',
              'text_property' => 'name',
              'minimum_input_length' => 1,
              'page_limit' => 30,
              'scroll' => true,
              'allow_clear' => true,
              'delay' => 250,
              'cache' => true,
              'cache_timeout' => 60000, // if 'cache' is true
              'language' => 'fr',
              'placeholder' => 'actions.selectOneOrManyTags',
              'width' => '100%',
              'attr' => [
                  'class' => 'select2 ajax-add-new-tag'
                ]  
            ])    

            ->add('coordinate', CoordinateType::class, [
               'label' => 'Person.field.coordinate',
                'required' => false,
               'personnalFieldsRestriction' => $options['personnalFieldsRestriction'],
              ])
            ->add('observation', null, ['label' => 'Person.field.observation'])
            ->add('isShared', null, ['label' => 'genericFields.isShared'])
            ->add('isEditableByOtherEntities', null, ['label' => 'genericFields.isEditableByOtherEntities'])
            ->add('nbMinorChildreen', null, ['label' => 'Person.field.nbMinorChildreen'])
            ->add('nbMajorChildreen', null, ['label' => 'Person.field.nbMajorChildreen'])
            ->add('dontWantToBeContacted', null, ['label' => 'Person.field.dontWantToBeContacted'])
        ;

        if ($user->hasRole('ROLE_CONTRIBUTOR') || $user->hasRole('ROLE_CONTRIBUTOR_PLUS') || $user->hasRole('ROLE_ADMIN')) {
            $builder->add('dontShowCoordinateForReaders', CheckboxType::class, [
                'label' => 'Person.field.dontShowCoordinateForReaders',
                'required' => false,
                ]);
        } else {
            $builder->add('dontShowCoordinateForReaders', HiddenType::class, [
                'label' => 'Person.field.dontShowCoordinateForReaders',
            ]);
        }

        $builder->get('birthLocation')
            ->addModelTransformer(new CityToNumberTransformer($this->manager));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $person = $event->getData();
            $form = $event->getForm();
            if ($person && $person->getId()) {
                $form->add('preferedEmails', null, [
                  'label' => 'Person.field.preferedEmails',
                  'class' => 'PostparcBundle:Email',
                  'required' => false,
                  'attr' => ['class' => 'select2'],  
                  'query_builder' => function (EntityRepository $er) use ($person) {
                      return $er->retrievePossiblePersonEmails($person->getId());
                  },
                ]);
            }
        });

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
         'user' => null,
        ]);
    }
}
