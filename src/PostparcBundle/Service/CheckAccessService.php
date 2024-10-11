<?php

namespace PostparcBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class CheckAccessService
{
    private $container;
    private $authorizationChecker;

    public function __construct(Container $container, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->container = $container;
        $this->authorizationChecker = $authorizationChecker;
    }

    public function checkAccess($object)
    {
        $currentEntityService = $this->container->get('postparc_current_entity_service');
        $userEntity = $currentEntityService->getCurrentEntity();

        // deletedAt control
        // prevent show deleted object form url
        if (is_callable([$object, 'getDeletedAt']) && $object->getDeletedAt() && !$this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return false;
        }

        if (!$object->getEntity() || !is_callable([$object, 'getIsShared']) || $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }
        return $userEntity == $object->getEntity() || (is_callable([$object, 'getIsShared']) && $object->getIsShared());
    }
}
