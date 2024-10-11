<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use PostparcBundle\Entity\User;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserFilterType extends AbstractType
{
    private $container;
    private $authorizationChecker;

    public function __construct(Container $container, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->container = $container;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('lastName', null, ['label' => 'User.field.lastName', 'required' => false])
            ->add('username', null, ['label' => 'User.field.username', 'required' => false])
        ;
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $builder->add('roles', ChoiceType::class, [
                'choices' => [
                    'User.roles.viewer' => 'ROLE_USER',
                    'User.roles.viewer_plus' => 'ROLE_USER_PLUS',
                    'User.roles.contributor' => 'ROLE_CONTRIBUTOR',
                    'User.roles.contributor_plus' => 'ROLE_CONTRIBUTOR_PLUS',
                    'User.roles.admin' => 'ROLE_ADMIN', ],
                 'label' => 'User.field.roles',
                 'expanded' => false,
                 'multiple' => true,
                 'required' => false,
                 'mapped' => true,
            ]);
        } elseif ($this->authorizationChecker->isGranted('ROLE_CONTRIBUTOR_PLUS')) {
            $builder->add('roles', ChoiceType::class, [
                'choices' => [
                    'User.roles.viewer' => 'ROLE_USER',
                    'User.roles.viewer_plus' => 'ROLE_USER_PLUS',
                    'User.roles.contributor' => 'ROLE_CONTRIBUTOR',
                    ],
                 'label' => 'User.field.roles',
                 'expanded' => false,
                 'multiple' => true,
                 'required' => false,
                 'mapped' => true,
            ]);
        } elseif ($this->authorizationChecker->isGranted('ROLE_CONTRIBUTOR')) {
            $builder->add('roles', ChoiceType::class, [
                'choices' => [
                    'User.roles.viewer' => 'ROLE_USER',
                    'User.roles.viewer_plus' => 'ROLE_USER_PLUS',
                ],
                 'label' => 'User.field.roles',
                 'expanded' => false,
                 'multiple' => true,
                 'required' => false,
                 'mapped' => true,
            ]);
        } else {
            $builder->add('roles', ChoiceType::class, [
                'choices' => [
                    'User.roles.viewer' => 'ROLE_USER', 
                    'User.roles.viewer_plus' => 'ROLE_USER_PLUS',
                    ],
                 'label' => 'User.field.roles',
                 'expanded' => false,
                 'multiple' => true,
                 'required' => false,
                 'mapped' => true,
            ]);
        }
        if ($this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            $builder->add('entity', EntityType::class, [
                'class' => 'PostparcBundle:Entity',
                'label' => 'User.field.entity',
                'required' => false,
            ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\User',
        ]);
        $resolver->setDefined('user');
        //$resolver->setRequired('user');
        $resolver->addAllowedTypes('user', User::class);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user_filter';
    }
}
