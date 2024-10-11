<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use PostparcBundle\Form\DataTransformer\EntityToNumberTransformer;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class UserType extends AbstractType
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
        parent::buildForm($builder, $options);

        $user = $options['user'];

        $builder
                ->add('firstName', null, ['label' => 'User.field.firstName'])
                ->add('lastName', null, ['label' => 'User.field.lastName'])
                ->add('enabled', null, [
                    'label' => 'User.field.enabled',
                ])
                ->add('plainPassword', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'options' => ['translation_domain' => 'FOSUserBundle'],
                    'first_options' => ['label' => 'form.password'],
                    'second_options' => ['label' => 'form.password_confirmation'],
                    'invalid_message' => 'fos_user.password.mismatch',
                    'required' => false,
                ])
                ->add('coordinate', CoordinateType::class, ['label' => 'genericFields.coordinate'])
        ;
        if ($user->hasRole('ROLE_SUPER_ADMIN') || $user->hasRole('ROLE_ADMIN_MULTI_INSTANCE')) {
            $builder->add('entity', null, ['label' => 'User.field.entity']);
        } else {
            $builder->add('entity', HiddenType::class, [
                'label' => 'User.field.entity',
            ]);
        }
        // ROLES
        if ($user->hasRole('ROLE_ADMIN') || $user->hasRole('ROLE_SUPER_ADMIN')) {
            $rolesChoices = [
                'User.roles.viewer' => 'ROLE_USER',
                'User.roles.viewer_plus' => 'ROLE_USER_PLUS',
                'User.roles.contributor' => 'ROLE_CONTRIBUTOR',
                'User.roles.contributor_plus' => 'ROLE_CONTRIBUTOR_PLUS',
                'User.roles.admin' => 'ROLE_ADMIN', ];
        } elseif ($user->hasRole('ROLE_CONTRIBUTOR_PLUS')) {
            $rolesChoices = [
                'User.roles.viewer' => 'ROLE_USER',
                'User.roles.viewer_plus' => 'ROLE_USER_PLUS',
                'User.roles.contributor' => 'ROLE_CONTRIBUTOR',
                'User.roles.contributor_plus' => 'ROLE_CONTRIBUTOR_PLUS',
            ];
        } elseif ($user->hasRole('ROLE_CONTRIBUTOR')) {
            $rolesChoices = [
                'User.roles.viewer' => 'ROLE_USER',
                'User.roles.viewer_plus' => 'ROLE_USER_PLUS',
                'User.roles.contributor' => 'ROLE_CONTRIBUTOR',
            ];
        } else {
            $rolesChoices = [
                'User.roles.viewer' => 'ROLE_USER',
                'User.roles.viewer_plus' => 'ROLE_USER_PLUS',
            ];
        }

        $builder->add('roles', ChoiceType::class, [
            'choices' => $rolesChoices,
            'label' => 'User.field.roles',
            'expanded' => true,
            'multiple' => true,
            'required' => false,
            'mapped' => true,
        ]);

        $builder
                ->add('wishesToBeInformedOfChanges', null, ['label' => 'User.field.wishesToBeInformedOfChanges', 'attr' => ['data-help' => 'User.help.wishesToBeInformedOfChanges']])
                ->remove('current_password')
        ;
        if (!$user->hasRole('ROLE_SUPER_ADMIN') && !$user->hasRole('ROLE_ADMIN_MULTI_INSTANCE')) {
            $builder->get('entity')->addModelTransformer(new EntityToNumberTransformer($this->manager));
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\User',
            'csrf_token_id' => 'profile',
            // BC for SF < 2.8
            'intention' => 'profile',
            'user' => null,
        ]);
    }

    public function getParent()
    {
        return 'FOS\UserBundle\Form\Type\ProfileFormType';
    }

    public function getName()
    {
        return 'fos_user';
    }
}
