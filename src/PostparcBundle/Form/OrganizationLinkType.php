<?php

namespace PostparcBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use PostparcBundle\Form\DataTransformer\OrganizationToNumberTransformer;
use Doctrine\Common\Persistence\ObjectManager;

class OrganizationLinkType extends AbstractType
{
    private $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

                ->add('organizationLinked', HiddenType::class, [
                    'invalid_message' => 'error.notAValidOrganizationNumber',
                    'required' => true,
                    'label' => 'OrganizationLink.field.organizationLinked',
                ])
                ->add('linkType', ChoiceType::class, [
                    'label' => 'OrganizationLink.field.linkType',
                    'expanded' => false,
                    'multiple' => false,
                    'choices' => [
                        'OrganizationLink.vertical' => '1',
                        'OrganizationLink.horizontal' => '2',
                        'OrganizationLink.service' => '3',
                    ],
                ])
                ->add('name', null, [
                    'label' => 'OrganizationLink.field.name',
                    'required' => false,
                ])
                ;
        $builder->get('organizationLinked')
                    ->addModelTransformer(new OrganizationToNumberTransformer($this->manager));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'PostparcBundle\Entity\OrganizationLink',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'postparcbundle_organizationlink';
    }
}
