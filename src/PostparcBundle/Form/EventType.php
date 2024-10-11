<?php

namespace PostparcBundle\Form;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use PostparcBundle\Service\UserConfigService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

//use FOS\CKEditorBundle\Form\Type\CKEditorType;

class EventType extends AbstractType
{
    private $manager;
    private $tokenStorage;
    private $userConfigService;

    public function __construct(
            ObjectManager $manager,
            TokenStorage $tokenStorage,
            UserConfigService $userConfigService
            )
    {
        $this->manager = $manager;
        $this->tokenStorage = $tokenStorage;
        $this->userConfigService = $userConfigService;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $editorFontFamily = $this->userConfigService
                ->getSummerNoteFontFamily($user);
        $editorFontSize = $this->userConfigService
                ->getSummerNoteFontSize($user);        
//        dump($editorFontFamily);
//        die;
        $builder
              ->add('eventType', EntityType::class, [
                'label' => 'Event.field.eventType',
                'class' => 'PostparcBundle:EventType',
                'placeholder' => 'actions.selectEventType',
                'required' => false,
                'attr' => ['class' => 'eventType-select2-autocomplete ajax-add-new-eventType'],
              ])
              ->add('name', null, ['label' => 'genericFields.name'])
              ->add('description', null, [
                'label' => 'Event.field.description',
                'attr' => [
                  'class' => 'summernote',
                  'data-default-font-family' => $editorFontFamily,
                  'data-default-font-size' => $editorFontSize
                ]
              ])
              ->add('date', DateTimeType::class, [
                'label' => 'Event.field.date',
                'required' => true,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd/MM/yyyy HH:mm',
                'with_seconds' => false,
                'attr' => [
                  'class' => 'js-datetimepicker',
                  'data-provide' => 'datetimepicker',
                ],
              ])
              ->add(
                  'duration',
                  DateIntervalType::class,
                  [
                  'label' => 'Event.field.duration',
                  'input' => 'string',
                  'required' => false,
                  'widget' => 'choice',
                  // choice fields to display
                  'with_years' => false,
                  'with_months' => false,
                  'with_days' => true,
                  'with_minutes' => true,
                  'with_hours' => true,
                  'with_seconds' => false,
                      ]
              )
              ->add('nbPlace', IntegerType::class, [
                'label' => 'Event.field.nbPlace',
                'required' => false,
              ])
              ->add('image', FileType::class, [
                'label' => 'Event.field.image',
                'required' => false,
                'data_class' => null,
                'attr' => [
                ],
              ])
              ->add('frequency', ChoiceType::class, [
                'label' => 'Event.field.frequency',
                'expanded' => false,
                'multiple' => false,
                'placeholder' => 'actions.selectFrequency',
                'choices' => [
                  'Event.punctual' => '1',
                  'Event.regular' => '2',
                ],
              ])
              ->add('organizators', EntityType::class, [
                'class' => 'PostparcBundle:User',
                'required' => false,
                'label' => 'Event.field.organizators',
                'multiple' => true,
                'attr' => ['class' => 'select2'],
                'placeholder' => 'actions.selectPerson',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('p')
                           ->orderBy('p.lastName, p.firstName', 'ASC')
                    ;
                },
              ])
              ->add('organizations', EntityType::class, [
                'class' => 'PostparcBundle:Organization',
                'required' => false,
                'label' => 'Event.field.organizations',
                'multiple' => true,
                'attr' => ['class' => 'select2'],
                'placeholder' => 'actions.selectOrganization',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')
                           ->orderBy('o.name', 'ASC')
                    ;
                },
              ])
              ->add('coordinate', CoordinateType::class, ['label' => 'Event.field.coordinate'])
//              ->add('tags', null, [
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
              ->add('isShared', null, ['label' => 'genericFields.isShared'])
              ->add('isEditableByOtherEntities', null, ['label' => 'genericFields.isEditableByOtherEntities'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => 'PostparcBundle\Entity\Event',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'postparcbundle_event';
    }
}
