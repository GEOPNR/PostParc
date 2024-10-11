<?php

namespace PostparcBundle\Form;

use PostparcBundle\Service\UserConfigService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

//use FOS\CKEditorBundle\Form\Type\CKEditorType;

class DocumentTemplateType extends AbstractType
{
    
    private $tokenStorage;
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
        
        $object = $builder->getData();

        $builder
              ->add('name', null, ['label' => 'DocumentTemplate.field.name', 'required' => true])
              ->add('description', null, ['label' => 'DocumentTemplate.field.description', 'required' => true])
              ->add('subject', null, [
                'label' => 'DocumentTemplate.field.subject',
                'attr' => [
                ],
              ])
              ->add('body', null, [
                'label' => 'DocumentTemplate.field.body',
                'attr' => [
                  'class' => 'summernote',
                  'data-default-font-family' => $editorFontFamily,
                  'data-default-font-size' => $editorFontSize                    
                ]
              ])
              ->add('mailable', null, ['label' => 'DocumentTemplate.field.mailable'])
              ->add('isActive', null, ['label' => 'genericFields.isActive'])
              ->add('marginTop', null, ['label' => 'DocumentTemplate.field.marginTop'])
              ->add('marginBottom', null, ['label' => 'DocumentTemplate.field.marginBottom'])
              ->add('marginLeft', null, ['label' => 'DocumentTemplate.field.marginLeft'])
              ->add('marginRight', null, ['label' => 'DocumentTemplate.field.marginRight'])
              ->add('printFooter', null, ['label' => 'DocumentTemplate.field.printFooter'])
              ->add('footer', null, ['label' => 'DocumentTemplate.field.footer'])
              ->add('printImage', null, ['label' => 'DocumentTemplate.field.printImage'])
              ->add('printImageAsBackground', null, ['label' => 'DocumentTemplate.field.printImageAsBackground'])
              ->add('image', FileType::class, [
                'label' => 'DocumentTemplate.field.image',
                'required' => false,
                'data_class' => null,
                'attr' => [
                ],
              ])
              /* ->add('attachment', AttachmentType::class, array(
                'label' => 'DocumentTemplate.field.attachment',
                'required' => false,
                'attr'  => array(
                'data-help'=>'DocumentTemplate.message.useOnlyForMassiveEmail'
                )
                )) */
              ->add('isShared', null, ['label' => 'genericFields.isShared'])
              ->add('isEditableByOtherEntities', null, ['label' => 'genericFields.isEditableByOtherEntities'])
        ;
        if(!($object->getId()) || ( $object && $object->getCreatedBy() && $object->getCreatedBy()->getId() == $user->getId() )) {
            $builder->add('isPrivate', null, ['label' => 'genericFields.isPrivate']);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => 'PostparcBundle\Entity\DocumentTemplate',
        'currentUser' => null    
        ]);
    }
}
