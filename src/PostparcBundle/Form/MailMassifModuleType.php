<?php

namespace PostparcBundle\Form;

use PostparcBundle\Service\UserConfigService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
//use FOS\CKEditorBundle\Form\Type\CKEditorType;

class MailMassifModuleType extends AbstractType
{
    
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
                ->add('subject', TextType::class, ['label' => 'sendMailMassifModule.fields.subject'])
                ->add('body', TextType::class, [
                    'label' => 'sendMailMassifModule.fields.body',
                    'required' => false,
                    'attr' => [
                        'class' => 'summernote',
                        'data-default-font-family' => $editorFontFamily,
                        'data-default-font-size' => $editorFontSize  
                    ]
                ])
                ->add('requestingAReadReceipt', CheckboxType::class, ['label' => 'sendMailMassifModule.fields.requestingAReadReceipt', 'required' => false])
                ->add('attachments', CollectionType::class, [
                    'label' => 'Organization.field.attachments',
                    'entry_type' => AttachmentType::class,
                    'allow_add' => true,
                    'label' => false,
                    'by_reference' => true,
                    'allow_delete' => true,
                    'required' => false,
                    'attr' => ['class' => 'hidden'],
                  ])    
        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
