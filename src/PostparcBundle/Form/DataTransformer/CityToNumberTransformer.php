<?php

namespace PostparcBundle\Form\DataTransformer;

use Doctrine\Common\Persistence\ObjectManager;
use PostparcBundle\Entity\City;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CityToNumberTransformer implements DataTransformerInterface
{
    /** @var ObjectManager */
    private $om;

    /** @param ObjectManager $om */
    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * Transforme un objet (City) en un Number (identifiant).
     *
     * @param City|null $city
     *
     * @return int
     */
    public function transform($city)
    {
        if (!$city instanceof City) {  // renvoie true si null est égal et de même type que $city
            return '';
        }

        return $city->getId();
    }

    /**
     * Transforme un Number (identifiant) en une entité (City).
     *
     * @param int $id
     *
     * @return City|null
     *
     * @throws TransformationFailedException si l'objet (City) n'est pas trouvé
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return;
        }

        // récupération de la catégorie
        $city = $this->om
            ->getRepository('PostparcBundle:City')
            ->find($id)
        ;

        // lance une exception si l'identifiant n'a pas été trouvé
        if (!$city instanceof City) {
            throw new TransformationFailedException(sprintf(
                'La commune ayant l\'identifiant "%s" n\'existe pas !',
                $id
            ));
        }

        return $city;
    }
}
