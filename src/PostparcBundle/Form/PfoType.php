<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Container;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class PfoType extends AbstractType {

    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    public $container;
    private $manager;

    public function __construct(ObjectManager $manager, Container $container) {
        $this->manager = $manager;
        $this->container = $container;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('organization', Select2EntityType::class, [
                    'multiple' => false,
                    'remote_route' => 'autocomplete_organization',
                    'class' => 'PostparcBundle:Organization',
                    'label' => 'Pfo.field.organization',
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
                    'placeholder' => 'actions.selectOrganization',
                        // 'object_manager' => $objectManager, // inject a custom object / entity manager
                ])
                ->add('person', Select2EntityType::class, [
                    'multiple' => false,
                    'remote_route' => 'autocomplete_person',
                    'class' => 'PostparcBundle:Person',
                    'label' => 'Pfo.field.person',
                    'primary_key' => 'id',
                    'text_property' => 'name',
                    'minimum_input_length' => 2,
                    'page_limit' => 30,
                    'allow_clear' => true,
                    'delay' => 250,
                    'scroll' => true,
                    'cache' => true,
                    'cache_timeout' => 60000, // if 'cache' is true
                    'language' => 'fr',
                    'placeholder' => 'actions.selectPerson',
                        // 'object_manager' => $objectManager, // inject a custom object / entity manager
                ])
                ->add('personFunction', Select2EntityType::class, [
                    'multiple' => false,
                    'remote_route' => 'autocomplete_function',
                    'class' => 'PostparcBundle:PersonFunction',
                    'label' => 'Pfo.field.personFunction',
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
                    'placeholder' => 'actions.selectFunction',
                    'attr' => ['class' => 'ajax-add-new-function'],
                        // 'object_manager' => $objectManager, // inject a custom object / entity manager
                ])
                ->add(
                        'isMainFunction',
                        null,
                        [
                            'label' => 'Pfo.field.isMainFunction',
                            'attr' => [],
                        ]
                )
                ->add('additionalFunction', Select2EntityType::class, [
                    'multiple' => false,
                    'remote_route' => 'autocomplete_additionalFunction',
                    'class' => 'PostparcBundle:AdditionalFunction',
                    'label' => 'Pfo.field.additionalFunction',
                    'primary_key' => 'id',
                    'text_property' => 'name',
                    'minimum_input_length' => 2,
                    'required' => false,
                    'page_limit' => 30,
                    'scroll' => true,
                    'allow_clear' => true,
                    'delay' => 250,
                    'cache' => true,
                    'cache_timeout' => 60000, // if 'cache' is true
                    'language' => 'fr',
                    'placeholder' => 'actions.selectAdditionalFunction',
                    'attr' => [
                        'class' => 'ajax-add-new-additionalFunction',
                    ],
                        // 'object_manager' => $objectManager, // inject a custom object / entity manager
                ])
                ->add('service', Select2EntityType::class, [
                    'multiple' => false,
                    'remote_route' => 'autocomplete_service',
                    'class' => 'PostparcBundle:Service',
                    'label' => 'Pfo.field.service',
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
                    'placeholder' => 'actions.selectService',
                    'attr' => [
                        'class' => 'ajax-add-new-service',
                        'data-help' => 'Organization.help.serviceWithSpecificCoordinate',
                    ],
                        // 'object_manager' => $objectManager, // inject a custom object / entity manager
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
                ->add('phone', null, ['label' => 'Pfo.field.phone'])
                ->add('mobilePhone', null, ['label' => 'Pfo.field.mobilePhone'])
                ->add('fax', null, ['label' => 'Pfo.field.fax'])
                ->add('assistantName', null, ['label' => 'Pfo.field.assistantName'])
                ->add('assistantPhone', null, ['label' => 'Pfo.field.assistantPhone'])
                ->add('connectingCity', Select2EntityType::class, [
                    'multiple' => false,
                    'label' => 'Pfo.field.connectingCity',
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
                    'placeholder' => 'actions.selectCity',
                        // 'object_manager' => $objectManager, // inject a custom object / entity manager
                ])
                ->add('email', EmailFormType::class, [
                    'label' => 'Coordinate.field.email',
                    'required' => false,
                ])
                ->add('hiringDate', DateType::class, [
                    'label' => 'Pfo.field.hiringDate',
                    'required' => false,
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd-MM-yyyy',
                    'attr' => ['class' => 'js-datepicker'],
                ])
                ->add('observation', null, ['label' => 'Pfo.field.observation'])
                ->add('isShared', null, ['label' => 'genericFields.isShared'])
                ->add('isEditableByOtherEntities', null, ['label' => 'genericFields.isEditableByOtherEntities'])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $pfo = $event->getData();
            $form = $event->getForm();
            if ($pfo && $pfo->getId()) {
                $form->add('preferedEmails', null, [
                    'label' => 'Person.field.preferedEmails',
                    'class' => 'PostparcBundle:Email',
                    'required' => false,
                    'attr' => ['class' => 'select2'],
                    'query_builder' => function (EntityRepository $er) use ($pfo) {
                        return $er->retrievePossiblePfoEmails($pfo);
                    },
                ]);
                $form->add('preferedCoordinateAddress', EntityType::class, [
                    'class' => 'PostparcBundle:Coordinate',
                    'required' => false,
                    'label' => 'Pfo.field.preferedCoordinateAddress',
                    'query_builder' => function (EntityRepository $er) use ($pfo) {
                        return $er->retrievePossiblePfoCoordinates($pfo);
                    },
                ]);
            }
        });
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\Pfo',
        ]);
    }

}
