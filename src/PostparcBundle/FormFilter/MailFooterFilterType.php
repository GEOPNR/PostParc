<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class MailFooterFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $user = $options['user'];
        $builder
            ->add('name', null, [
              'label' => 'MailFooter.field.name',
              'required' => false
              ]);
        if ($user->hasRole('ROLE_ADMIN')) {
            $builder->add('user', Select2EntityType::class, [
              'multiple' => false,
              'remote_route' => 'autocomplete_user',
              'class' => 'PostparcBundle:User',
              'label' => 'MailFooter.field.user',
              'primary_key' => 'id',
              'text_property' => 'displayName',
              'minimum_input_length' => 2,
              'page_limit' => 30,
              'scroll' => true,
              'allow_clear' => true,
              'delay' => 250,
              'cache' => true,
              'cache_timeout' => 60000, // if 'cache' is true
              'language' => 'fr',
              'placeholder' => 'actions.selectUser',
              'width' => '100%',
            ]);
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
