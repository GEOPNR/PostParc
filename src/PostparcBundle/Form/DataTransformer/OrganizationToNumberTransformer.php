<?php

namespace PostparcBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use PostparcBundle\Entity\Organization;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class OrganizationToNumberTransformer implements DataTransformerInterface
{
    /** @var ObjectManager */
    private $om;

    /** @param ObjectManager $om */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Transforme un objet (Organization) en un Number (identifiant).
     *
     * @param Organization|null $organization
     *
     * @return int
     */
    public function transform($organization)
    {
        if (!$organization instanceof Organization) {  // renvoie true si null est égal et de même type que $organization
            return '';
        }

        return $organization->getId();
    }

    /**
     * Transforme un Number (identifiant) en une entité (Organization).
     *
     * @param int $id
     *
     * @return Organization|null
     *
     * @throws TransformationFailedException si l'objet (Organization) n'est pas trouvé
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return;
        }

        // récupération de la catégorie
        $organization = $this->om
            ->getRepository('PostparcBundle:Organization')
            ->find($id)
        ;

        // lance une exception si l'identifiant n'a pas été trouvé
        if (!$organization instanceof Organization) {
            throw new TransformationFailedException(sprintf(
                'La commune ayant l\'identifiant "%s" n\'existe pas !',
                $id
            ));
        }

        return $organization;
    }
}
