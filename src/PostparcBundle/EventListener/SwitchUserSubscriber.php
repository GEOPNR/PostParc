<?php

namespace PostparcBundle\EventListener;

/**
 * Description of SwitchUserSubscriber
 *
 * @author philg
 */

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class SwitchUserSubscriber implements EventSubscriberInterface
{
    public function onSwitchUser(SwitchUserEvent $event)
    {
        // update value of currentId in session
        $event->getRequest()->getSession()->set(
            'currentEntityId',
            $event->getTargetUser()->getEntity()->getId()
        );
    }

    public static function getSubscribedEvents()
    {
        return [
            // constant for security.switch_user
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }
}
