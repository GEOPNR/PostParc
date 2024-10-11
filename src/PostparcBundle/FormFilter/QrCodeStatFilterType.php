<?php

namespace PostparcBundle\FormFilter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class QrCodeStatFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        
          
        $builder->add('startDate', DateType::class, [
                'label' => 'QrCodeStat.field.startDate',
                 'required' => false,
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd-MM-yyyy',
                    'attr' => ['class' => 'js-datepicker']
            
            ]);
            $builder->add('endDate', DateType::class, [
                'label' => 'QrCodeStat.field.endDate',
                    'required' => false,
                    'widget' => 'single_text',
                    'html5' => false,
                    'format' => 'dd-MM-yyyy',
                    'attr' => ['class' => 'js-datepicker']
            ]);
            
            $builder->add('className', ChoiceType::class, [
                'label' => 'QrCodeStat.field.className',
                'choices' => [
                    'QrCodeStat.className.Person' => 'Person',
                    'QrCodeStat.className.Organization' => 'Organization',
                    'QrCodeStat.className.Pfo' => 'Pfo',
                    'QrCodeStat.className.Representation' => 'Representation'
                    ],
                 'expanded' => false,
                 'multiple' => true,
                 'required' => false,
                 'mapped' => false,
                'attr' => ['class' => 'select2']
            ]);  
            
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
//            'data_class' => 'PostparcBundle\Entity\Tag',
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'QrCodeStat_filter';
    }
}
