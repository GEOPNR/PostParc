<?php

namespace PostparcBundle\Form;

use PostparcBundle\Service\UserConfigService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

//use FOS\CKEditorBundle\Form\Type\CKEditorType;

class MailFooterType extends AbstractType
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
              ->add('name', null, ['label' => 'MailFooter.field.name'])
              ->add('footer', null, [
                'label' => 'MailFooter.field.footer',
                'attr' => [
                  'class' => 'summernote',
                  'data-default-font-family' => $editorFontFamily,
                  'data-default-font-size' => $editorFontSize                       
                ]
                ])
        ;
        if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_SUPER_ADMIN')) {
            $builder->add('user', null, ['label' => 'MailFooter.field.user']);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
        'data_class' => 'PostparcBundle\Entity\MailFooter',
        'user' => null,
        ]);
    }
}
