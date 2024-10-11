<?php

namespace PostparcBundle\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Doctrine\ORM\EntityManager;

class CurrentEntityService
{
    private $current_user;
    private $session;
    private $em;

    public function __construct(TokenStorage $security_context, Session $session, EntityManager $em)
    {
        if (null != $security_context->getToken()) {
            $this->current_user = $security_context->getToken()->getUser();
        }
        $this->session = $session;
        $this->em = $em;
    }

    public function getCurrentEntityID()
    {
        if ($this->current_user->hasRole('ROLE_SUPER_ADMIN')) {
            return null;
        }
        if ($this->session->has('currentEntityId')) {
            $entityId = $this->session->get('currentEntityId');
        } else {
            $entityId = $this->current_user->getEntity()->getId();
        }

        return $entityId;
    }

    public function getCurrentEntity()
    {
        if ($this->current_user->hasRole('ROLE_SUPER_ADMIN')) {
            return null;
        }
        if ($this->session->has('currentEntityId')) {
            $entity = $this->em->getRepository('PostparcBundle:Entity')->find($this->session->get('currentEntityId'));
        } else {
            $entity = $this->current_user->getEntity();
        }

        return $entity;
    }
}
