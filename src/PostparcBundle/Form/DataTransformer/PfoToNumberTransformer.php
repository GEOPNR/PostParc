<?php

namespace PostparcBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use PostparcBundle\Entity\Pfo;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PfoToNumberTransformer implements DataTransformerInterface
{
    /** @var ObjectManager */
    private $om;

    /** @param ObjectManager $om */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Transforme un objet (Pfo) en un Number (identifiant).
     *
     * @param Pfo|null $organization
     *
     * @return int
     */
    public function transform($organization)
    {
        if (!$organization instanceof Pfo) {  // renvoie true si null est égal et de même type que $organization
            return '';
        }

        return $organization->getId();
    }

    /**
     * Transforme un Number (identifiant) en une entité (Pfo).
     *
     * @param int $id
     *
     * @return Pfo|null
     *
     * @throws TransformationFailedException si l'objet (Pfo) n'est pas trouvé
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return;
        }

        // récupération de la catégorie
        $organization = $this->om
            ->getRepository('PostparcBundle:Pfo')
            ->find($id)
        ;

        // lance une exception si l'identifiant n'a pas été trouvé
        if (!$organization instanceof Pfo) {
            throw new TransformationFailedException(sprintf(
                'La personne qualifiée ayant l\'identifiant "%s" n\'existe pas !',
                $id
            ));
        }

        return $organization;
    }
}
