<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EventRepresentations.
 *
 * @ORM\Table(name="event_representations")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\EventRepresentationsRepository")
 */
class EventRepresentations
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Representation", inversedBy="eventRepresentations")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE",nullable=false)
     */
    protected $representation;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="eventRepresentations")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $event;

    /**
     * @var string
     *
     * @ORM\Column(name="confirmationToken", type="string", length=50)
     */
    private $confirmationToken;
    
    /**
     * @var string
     *
     * @ORM\Column(name="representedBy", type="string", length=250, nullable=true)
     */
    private $representedBy;  

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="representedByDate", type="datetime", nullable=true)
     */
    private $representedByDate;    

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="confirmationDate", type="datetime", nullable=true)
     */
    private $confirmationDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="unconfirmationDate", type="datetime", nullable=true)
     */
    private $unconfirmationDate;
    

    /**
     * Set confirmationToken.
     *
     * @param string $confirmationToken
     *
     * @return EventRepresentations
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function __construct()
    {
        $this->confirmationToken = sha1(random_bytes(10));
    }

    /**
     * Get confirmationToken.
     *
     * @return string
     */
    public function getConfirmationToken()
    {
        return $this->confirmationToken;
    }

    /**
     * Set representation.
     *
     *
     * @return EventRepresentations
     */
    public function setRepresentation(\PostparcBundle\Entity\Representation $representation)
    {
        $this->representation = $representation;

        return $this;
    }

    /**
     * Get representation.
     *
     * @return \PostparcBundle\Entity\Representation
     */
    public function getRepresentation()
    {
        return $this->representation;
    }

    /**
     * Set event.
     *
     *
     * @return EventRepresentations
     */
    public function setEvent(\PostparcBundle\Entity\Event $event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event.
     *
     * @return \PostparcBundle\Entity\Event
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set confirmationDate.
     *
     * @param \DateTime|\DateTimeImmutable $confirmationDate
     *
     * @return EventRepresentations
     */
    public function setConfirmationDate(\DateTimeInterface $confirmationDate = null)
    {
        $this->confirmationDate = $confirmationDate;

        return $this;
    }

    /**
     * Get confirmationDate.
     *
     * @return \DateTime|null
     */
    public function getConfirmationDate()
    {
        return $this->confirmationDate;
    }

    /**
     * Set unconfirmationDate.
     *
     * @param \DateTime|\DateTimeImmutable $unconfirmationDate
     *
     * @return EventRepresentations
     */
    public function setUnconfirmationDate(\DateTimeInterface $unconfirmationDate = null)
    {
        $this->unconfirmationDate = $unconfirmationDate;

        return $this;
    }

    /**
     * Get unconfirmationDate.
     *
     * @return \DateTime|null
     */
    public function getUnconfirmationDate()
    {
        return $this->unconfirmationDate;
    }


    /**
     * Set representedBy.
     *
     * @param string|null $representedBy
     *
     * @return EventRepresentations
     */
    public function setRepresentedBy($representedBy = null)
    {
        $this->representedBy = $representedBy;

        return $this;
    }

    /**
     * Get representedBy.
     *
     * @return string|null
     */
    public function getRepresentedBy()
    {
        return $this->representedBy;
    }

    /**
     * Set representedByDate.
     *
     * @param \DateTime|null $representedByDate
     *
     * @return EventRepresentations
     */
    public function setRepresentedByDate($representedByDate = null)
    {
        $this->representedByDate = $representedByDate;

        return $this;
    }

    /**
     * Get representedByDate.
     *
     * @return \DateTime|null
     */
    public function getRepresentedByDate()
    {
        return $this->representedByDate;
    }
}
