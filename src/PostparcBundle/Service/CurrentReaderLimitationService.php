<?php

namespace PostparcBundle\Service;

use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ORM\EntityManager;

class CurrentReaderLimitationService
{
    private $current_user;
    private $session;
    private $em;

    public function __construct($security_context, Session $session, EntityManager $em)
    {
        if (null != $security_context->getToken()) {
            $this->current_user = $security_context->getToken()->getUser();
        }
        $this->session = $session;
        $this->em = $em;
    }

    public function getReaderLimitations()
    {
        if (count($this->current_user->getRoles()) > 1) { // pas un lecteur
            return null;
        }
        $limitations = null;
        // recupération entité courante pour les lecteurs uniquements
        if ($this->session->has('currentEntityId')) {
            $entityId = $this->session->get('currentEntityId');
        } else {
            $entityId = $this->current_user->getEntity()->getId();
        }

        if ($this->session->has('currentReaderLimitations')) {
            $limitations = $this->session->get('currentReaderLimitations');
        } else {
            $readerLimitation = $this->em->getRepository('PostparcBundle:ReaderLimitation')->findOneBy(['entity' => $entityId]);
            if ($readerLimitation) {
                $this->session->set('currentReaderLimitations', $readerLimitation->getLimitations());
                $limitations = $readerLimitation->getLimitations();
            }
        }

        return $limitations;
    }
}
