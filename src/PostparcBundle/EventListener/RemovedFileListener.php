<?php

namespace PostparcBundle\EventListener;

use Vich\UploaderBundle\Event\Event;

class RemovedFileListener
{
    /**
     * @var \Doctrine\ORM\EntityManager|mixed
     */
    public $em;
    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * make sure a file entity object is removed after the file is deleted.
     *
     * @param Event $event
     */
    public function onPostRemove(Event $event)
    {
        // get the file object
        $removedFile = $event->getObject();
        // remove the file object from the database
        $this->em->getRepository('PostparcBundle:Attachment')->forceDelete($removedFile->getId());
    }
}
