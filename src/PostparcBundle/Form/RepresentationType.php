<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use PostparcBundle\Form\DataTransformer\OrganizationToNumberTransformer;
use PostparcBundle\Form\DataTransformer\PersonToNumberTransformer;
use PostparcBundle\Form\DataTransformer\PfoToNumberTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\DependencyInjection\Container;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class RepresentationType extends AbstractType
{
    /**
     * @var \Symfony\Component\DependencyInjection\Container|mixed
     */
    public $container;
    private $manager;

    public function __construct(ObjectManager $manager, Container $container)
    {
        $this->manager = $manager;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $entityId = $currentEntityService->getCurrentEntityID();

        $builder
            ->add('elected', ChoiceType::class, [
                    'label' => 'Representation.field.elected',
                    'expanded' => false,
                    'multiple' => false,
                    'required' => false,
                    'placeholder' => '',
                    'choices' => [
                        'Representation.elected' => '0',
                        'Representation.designated' => '1',
                    ],
            ])
            ->add('mandateType', EntityType::class, [
                'label' => 'Representation.field.mandateType',
                'required' => true,
                'multiple' => false,
                'class' => 'PostparcBundle:MandateType',
                'placeholder' => 'actions.selectMandateType',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('mt')
                        ->orderBy('mt.name', 'ASC')
                    ;
                },
                'attr' => ['class' => 'mandateType-select2-autocomplete ajax-add-new-mandateType'],
                ])
            ->add('coordinate', CoordinateType::class, [
                'label' => 'genericFields.coordinate',
                'attr' => [
                    'class' => 'no-toggle',
                    ],
                ])
            ->add('beginDate', DateType::class, [
                'label' => 'Representation.field.beginDate',
                'required' => false,
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'dd-MM-yyyy',
                'attr' => ['class' => 'js-datepicker'],
            ])
            ->add('mandatDuration', null, ['label' => 'Representation.field.mandatDuration', 'required' => false,
                'attr' => ['append' => 'units.inMonth', 'min' => 0], ])
            ->add('mandatDurationIsUnknown', null, ['label' => 'Representation.field.mandatDurationIsUnknown'])
            ->add('estimatedTime', null, ['label' => 'Representation.field.estimatedTime',
                'attr' => ['append' => 'units.hoursByMonth', 'min' => 0], ])
            ->add('nbMonthBeforeAlert', null, [
                'label' => 'Representation.field.nbMonthBeforeAlert',
                'required' => false,
                'attr' => [
                    'append' => 'units.inMonth',
                    'class' => 'representation-alert',
                    'min' => 0
                    ],
            ])
            ->add('sendAlert', null, [
                'label' => 'Representation.field.sendAlert',
                'attr' => [
                    'class' => 'representation-alert',
                    ],
                ])
            ->add('estimatedCost', null, ['label' => 'Representation.field.estimatedCost',
            'attr' => ['append' => 'units.euro', 'min' => 0], ])
            ->add('periodicity', null, ['label' => 'Representation.field.periodicity',
            'attr' => ['append' => 'units.numberPerYear', 'min' => 0], ])
            ->add('attachments', CollectionType::class, [
                'label' => 'genericFields.attachments',
                'entry_type' => AttachmentType::class,
                'allow_add' => true,
                'by_reference' => true,
                'allow_delete' => true,
                'required' => false,
            ])
            ->add('organization', HiddenType::class, [
                'invalid_message' => 'error.notAValidOrganizationNumber',
                'required' => true,
                'label' => 'Event.field.organization',
            ])
            ->add('alerter', EntityType::class, [
                    'class' => 'PostparcBundle:User',
                    'required' => false,
                    'label' => 'Representation.field.alerter',
                    'multiple' => false,
                    'attr' => [
                        'class' => 'select2 representation-alert',
                        'style' => 'width:100%;',
                        ],
                    'placeholder' => 'actions.selectPerson',
                    'query_builder' => function (EntityRepository $er) use ($entityId) {
                        if ($entityId) {
                            return $er->createQueryBuilder('p')
                                ->leftJoin('p.entity', 'e')
                                ->where('e.id=' . $entityId)
                                ->orderBy('p.lastName, p.firstName', 'ASC')
                                ;
                        } else {
                            return $er->createQueryBuilder('p')
                                ->orderBy('p.lastName, p.firstName', 'ASC')
                            ;
                        }
                    },
            ])
            ->add('service', EntityType::class, [
                'label' => 'Representation.field.service',
                'class' => 'PostparcBundle:Service',
                'required' => false,
                'placeholder' => 'actions.selectService',
                'attr' => [
                    'class' => 'service-select2-autocomplete ajax-add-new-service',
                    'data-help' => 'Organization.help.serviceWithSpecificCoordinate',
                    ],
            ])
            ->add('observation', TextareaType::class, ['label' => 'Representation.field.observation', 'required' => false])
            ->add('isShared', null, ['label' => 'genericFields.isShared'])
            ->add('isEditableByOtherEntities', null, ['label' => 'genericFields.isEditableByOtherEntities'])
            ->add('personFunction', EntityType::class, [
                'label' => 'Representation.field.personFunction',
                'class' => 'PostparcBundle:PersonFunction',
                'placeholder' => 'actions.selectFunction',
                'required' => false,
                'attr' => ['class' => 'function-select2-autocomplete ajax-add-new-function'],
            ])
            ->add('natureOfRepresentation', EntityType::class, [
                'label' => 'Representation.field.natureOfRepresentation',
                'required' => false,
                'class' => 'PostparcBundle:NatureOfRepresentation',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('nr')
                            ->orderBy('nr.name', 'ASC')
                        ;
                },
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

        ;

        // add person selector
        if (isset($options['attr']['allowChangePerson']) && true == $options['attr']['allowChangePerson']) {
            $builder->add('person', HiddenType::class, [
                'invalid_message' => 'error.notAValidPersonNumber',
                'required' => true,
                'label' => 'Representation.field.person',
            ]);
            $builder->get('person')
            ->addModelTransformer(new PersonToNumberTransformer($this->manager));
        }
        // add pfo selector
        if (isset($options['attr']['allowChangePfo']) && true == $options['attr']['allowChangePfo']) {
            $builder->add('pfo', HiddenType::class, [
                'invalid_message' => 'error.notAValidPfoNumber',
                'required' => true,
                'label' => 'Representation.field.pfo',
            ]);
            $builder->get('pfo')
            ->addModelTransformer(new PfoToNumberTransformer($this->manager));
        }

        $builder->get('organization')
            ->addModelTransformer(new OrganizationToNumberTransformer($this->manager));

        // add preferedCoordinateAddress and preferedEmail field when editing object
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $representation = $event->getData();
            $form = $event->getForm();
            if ($representation && $representation->getId()) {
                $form->add('preferedEmail', null, [
                    'label' => 'Representation.field.preferedEmail',
                    'class' => 'PostparcBundle:Email',
                    'required' => false,
                    'query_builder' => function (EntityRepository $er) use ($representation) {
                        return $er->retrievePossibleRepresentationEmails($representation);
                    },
                ]);
                $form->add('preferedCoordinateAddress', EntityType::class, [
                    'label' => 'Representation.field.preferedCoordinateAddress',
                    'class' => 'PostparcBundle:Coordinate',
                    'required' => false,
                    'query_builder' => function (EntityRepository $er) use ($representation) {
                        return $er->retrievePossibleRepresentationCoordinates($representation);
                    },
                ]);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\Representation',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'postparcbundle_representation';
    }
}
