<?php

namespace PostparcBundle\Form;

use PostparcBundle\Service\UserConfigService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

//use FOS\CKEditorBundle\Form\Type\CKEditorType;

class NoteType extends AbstractType {

    protected $tokenStorage;
    private $userConfigService;

    public function __construct(
            TokenStorage $tokenStorage,
            UserConfigService $userConfigService
            )
    {
        $this->tokenStorage = $tokenStorage;
        $this->userConfigService = $userConfigService;
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $user = $this->tokenStorage->getToken()->getUser();
        $editorFontFamily = $this->userConfigService
                ->getSummerNoteFontFamily($user);
        $editorFontSize = $this->userConfigService
                ->getSummerNoteFontSize($user);  
        
        $builder
                ->add('date', DateTimeType::class, [
                    'label' => 'genericFields.date',
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
                ->add('title', null, ['label' => 'Note.field.title'])
                ->add('content', null, [
                    'label' => 'Note.field.content',
                    'attr' => [
                        'class' => 'summernote',
                        'data-default-font-family' => $editorFontFamily,
                        'data-default-font-size' => $editorFontSize                         
                    ]
                ])
//                ->add('tags', null, [
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
                ->add('isPrivate', null, ['label' => 'genericFields.isPrivate'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\Note',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'postparcbundle_note';
    }

}
