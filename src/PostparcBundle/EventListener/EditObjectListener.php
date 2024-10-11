<?php

namespace PostparcBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Translation\TranslatorInterface;
use PostparcBundle\Entity\User;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EditObjectListener
{
    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface|mixed
     */
    public $tokenStorage;
    /**
     * @var \PostparcBundle\Entity\User|mixed
     */
    public $current_user;
    private $requestStack;

    /**
     * @param TranslatorInterface   $translator
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(RequestStack $requestStack, TokenStorageInterface $tokenStorage)
    {
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param LifecycleEventArgs $args
     *
     * @throws AccessDeniedException
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        if (is_object($this->tokenStorage->getToken())) {
            $entity = $args->getEntity();
            if ($entity instanceof User) {
                $this->current_user = $entity;
            } else {
                $this->current_user = $this->tokenStorage->getToken()->getUser();
            }

            // test if object has method getIsShared
            $parameters = $this->requestStack->getCurrentRequest()->attributes->all();
            if (is_callable([$entity, 'getIsShared']) && $entity->getEntity() && isset($parameters['_route']) && (false != strpos($parameters['_route'], '_edit') || false != strpos($parameters['_route'], '_delete')) && ($this->current_user->getEntity()->getId() != $entity->getEntity()->getId() && false === $entity->getIsEditableByOtherEntities() && false === $this->current_user->hasRole('ROLE_SUPER_ADMIN'))) {
                throw new AccessDeniedException('You cannot access this page !');
            }
        }
    }
}
