<?php

namespace PostparcBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use PostparcBundle\Entity\Attachement;

class PostPersistFileListener
{
    /**
     * LifecycleEventArgs $args.
     *
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if ($entity instanceof Attachement && is_null($entity->getAttachmentName())) {
            $entityManager = $args->getEntityManager();
            $entityManager->remove($entity);
            $entityManager->flush();
        }
    }
}
