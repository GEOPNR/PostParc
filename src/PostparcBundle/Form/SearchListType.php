<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchListType extends AbstractType
{

    
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $object = $builder->getData();
        $user = $options['currentUser'];
        
        $builder
            //->add('slug')
            ->add('name', null, ['label' => 'SearchList.field.name'])
            ->add('description', null, ['label' => 'SearchList.field.description'])
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
            'data_class' => 'PostparcBundle\Entity\SearchList',
            'currentUser' => null
        ]);
    }
}
