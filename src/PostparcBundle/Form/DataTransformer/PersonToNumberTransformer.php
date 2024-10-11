<?php

namespace PostparcBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use PostparcBundle\Entity\Person;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PersonToNumberTransformer implements DataTransformerInterface
{
    /** @var ObjectManager */
    private $om;

    /** @param ObjectManager $om */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Transforme un objet (Person) en un Number (identifiant).
     *
     * @param Person|null $organization
     *
     * @return int
     */
    public function transform($organization)
    {
        if (!$organization instanceof Person) {  // renvoie true si null est égal et de même type que $organization
            return '';
        }

        return $organization->getId();
    }

    /**
     * Transforme un Number (identifiant) en une entité (Person).
     *
     * @param int $id
     *
     * @return Person|null
     *
     * @throws TransformationFailedException si l'objet (Person) n'est pas trouvé
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return;
        }

        // récupération de la catégorie
        $organization = $this->om
            ->getRepository('PostparcBundle:Person')
            ->find($id)
        ;

        // lance une exception si l'identifiant n'a pas été trouvé
        if (!$organization instanceof Person) {
            throw new TransformationFailedException(sprintf(
                'La personne ayant l\'identifiant "%s" n\'existe pas !',
                $id
            ));
        }

        return $organization;
    }
}
