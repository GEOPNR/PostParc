<?php

namespace PostparcBundle\Form;

use PostparcBundle\Entity\Organization;
use PostparcBundle\Service\UserConfigService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

//use FOS\CKEditorBundle\Form\Type\CKEditorType;

class OrganizationType extends AbstractType {

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
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) 
    {
        $user = $this->tokenStorage->getToken()->getUser();
        $editorFontFamily = $this->userConfigService
                ->getSummerNoteFontFamily($user);
        $editorFontSize = $this->userConfigService
                ->getSummerNoteFontSize($user);  
        
        $builder
                ->add('image', FileType::class, [
                    'label' => 'Organization.field.image',
                    'required' => false,
                    'data_class' => null,
                    'attr' => [
                    ],
                ])
                ->add('name', null, ['label' => 'Organization.field.name'])
                ->add('abbreviation', null, ['label' => 'Organization.field.abbreviation'])
                ->add('siret', null, ['label' => 'Organization.field.siret'])
                ->add('organizationType', Select2EntityType::class, [
                    'multiple' => false,
                    'label' => 'Organization.field.organizationType',
                    'remote_route' => 'autocomplete_organizationType',
                    'class' => 'PostparcBundle:OrganizationType',
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
                    'placeholder' => 'actions.selectOrganizationType',
                        // 'object_manager' => $objectManager, // inject a custom object / entity manager
                ])
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
                ->add('nbAdherent', null, ['label' => 'Organization.field.nbAdherent'])
                ->add('description', null, [
                    'label' => 'Organization.field.description',
                    'attr' => [
                        'class' => 'summernote',
                        'data-default-font-family' => $editorFontFamily,
                        'data-default-font-size' => $editorFontSize                          
                    ]
                ])
                ->add('coordinate', CoordinateType::class, [
                    'label' => 'Organization.field.coordinate',
                    'attr' => [
                        'class' => 'no-toggle',
                    ],
                ])
                ->add('attachments', CollectionType::class, [
                    'label' => 'Organization.field.attachments',
                    'entry_type' => AttachmentType::class,
                    'allow_add' => true,
                    'by_reference' => true,
                    'allow_delete' => true,
                    'required' => false,
                ])
                ->add('observation', null, ['label' => 'Organization.field.observation'])
                ->add('showObservation', null, ['label' => 'showObservation'])
                ->add('isShared', null, ['label' => 'genericFields.isShared'])
                ->add('isEditableByOtherEntities', null, ['label' => 'genericFields.isEditableByOtherEntities'])
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults([
            'data_class' => Organization::class,
            'cascade_validation' => true,
        ]);
    }

}
