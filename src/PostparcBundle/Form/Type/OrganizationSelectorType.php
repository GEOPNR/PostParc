<?php

namespace PostparcBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use PostparcBundle\Form\DataTransformer\OrganizationToNumberTransformer;
use Doctrine\Common\Persistence\ObjectManager;

class OrganizationSelectorType extends AbstractType
{
    /** @var ObjectManager */
    private $om;

    /** @param ObjectManager $om */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transformer = new OrganizationToNumberTransformer($this->om);
        $builder->addModelTransformer($transformer);  // Ajout du convertissseur
    }

    public function getParent()
    {
        return 'text';  // hérite du champ text
    }

    public function getName()
    {
        return 'organization_selector';
    }
}
