<?php

namespace PostparcBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

class AffectEntityListener
{
    /**
     * @param LifecycleEventArgs $args
     */
    public function PostPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        // test if object has method getEntity
        if (is_callable([$entity, 'getEntity']) && !$entity->getEntity() && is_callable([$entity, 'getCreatedBy'])) {
            // recuperation createur
            $creator = $entity->getCreatedBy();
            if ($creator) {
                $userEntity = $creator->getEntity();
                $entity->setEntity($userEntity);
                $entityManager = $args->getEntityManager();
                $entityManager->persist($entity);
                $entityManager->flush();
            }
        }
    }
}
