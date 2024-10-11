<?php

namespace PostparcBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use PostparcBundle\Entity\Entity;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class EntityToNumberTransformer implements DataTransformerInterface
{
    /** @var ObjectManager */
    private $om;

    /** @param ObjectManager $om */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Transforme un objet (Entity) en un Number (identifiant).
     *
     * @param Entity|null $entity
     *
     * @return int
     */
    public function transform($entity)
    {
        if (!$entity instanceof Entity) {  // renvoie true si null est égal et de même type que $entity
            return '';
        }

        return $entity->getId();
    }

    /**
     * Transforme un Number (identifiant) en une entité (Entity).
     *
     * @param int $id
     *
     * @return Entity|null
     *
     * @throws TransformationFailedException si l'objet (Entity) n'est pas trouvé
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return;
        }

        // récupération de la catégorie
        $entity = $this->om
            ->getRepository('PostparcBundle:Entity')
            ->find($id)
        ;

        // lance une exception si l'identifiant n'a pas été trouvé
        if (!$entity instanceof Entity) {
            throw new TransformationFailedException(sprintf(
                'La commune ayant l\'identifiant "%s" n\'existe pas !',
                $id
            ));
        }

        return $entity;
    }
}
