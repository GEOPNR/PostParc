<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EventPfos.
 *
 * @ORM\Table(name="event_pfos")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\EventPfosRepository")
 */
class EventPfos
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Pfo", inversedBy="eventPfos")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE",nullable=false)
     */
    protected $pfo;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="eventPfos")
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
     * @return EventPfos
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
     * Set pfo.
     *
     *
     * @return EventPfos
     */
    public function setPfo(\PostparcBundle\Entity\Pfo $pfo)
    {
        $this->pfo = $pfo;

        return $this;
    }

    /**
     * Get pfo.
     *
     * @return \PostparcBundle\Entity\Pfo
     */
    public function getPfo()
    {
        return $this->pfo;
    }

    /**
     * Set event.
     *
     *
     * @return EventPfos
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
     * @return EventPfos
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
     * @return EventPs
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
     * @return EventPfos
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
     * @return EventPfos
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
