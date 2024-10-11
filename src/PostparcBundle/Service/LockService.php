<?php

namespace PostparcBundle\Service;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Doctrine\ORM\EntityManager;

class LockService
{
    /**
     * @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface|mixed
     */
    public $authorizationChecker;
    private $current_user;
    private $em;

    public function __construct(TokenStorage $security_context, EntityManager $em, AuthorizationCheckerInterface $authorizationChecker)
    {
        if (null != $security_context->getToken()) {
            $this->current_user = $security_context->getToken()->getUser();
        }
        $this->authorizationChecker = $authorizationChecker;

        $this->em = $em;
    }

    /**
     *
     * @param Object $object
     * @param int $duration max lock duration in seconds
     * @return boolean
     */
    public function isLock($object, $duration = 30)
    {
        if (!$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN') && is_callable([$object, 'getLockedBy'])) {
            $now = new \DateTime();
            if ($object->getLockedBy() && $this->current_user != $object->getLockedBy()) {
                // lock by an other user
                // check if the date is not older than max duration
                $diff = $now->getTimestamp() - $object->getLockedAt()->getTimestamp();
                if ($diff > $duration) {
                    $this->lockObject($object);
                    return false;
                }
                return true;
            } else {
                // lock the object
                $object = $this->lockObject($object);
            }
        }

        return false;
    }
    /**
     * add lock information on an object
     * @param Object $object
     */
    public function lockObject($object)
    {
        $now = new \DateTime();
        $object->setLockedBy($this->current_user);
        $object->setLockedAt($now);
        $this->em->persist($object);
        $this->em->flush();
    }
    /**
     * remove lock information on an object
     * @param type $object
     */
    public function unlockObject($object)
    {
        if (is_callable([$object, 'getLockedBy'])) {
            $object->setLockedBy(null);
            $object->setLockedAt(null);
            $this->em->persist($object);
            $this->em->flush();
        }
    }
}
