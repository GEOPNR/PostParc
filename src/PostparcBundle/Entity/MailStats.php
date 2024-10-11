<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MailStats.
 *
 * @ORM\Table(name="mail_stats")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\MailStatsRepository")
 */
class MailStats
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=128)
     */
    private $token;

    /**
     * @var int
     *
     * @ORM\Column(name="nbEmail", type="integer")
     */
    private $nbEmail;

    /**
     * @var int
     *
     * @ORM\Column(name="nbOpenedEmail", type="integer")
     */
    private $nbOpenedEmail = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=255)
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text", nullable=true,options={"collate"="utf8mb4_unicode_ci","charset":"utf8mb4"})
     */
    private $body;

    /**
     * @var float
     *
     * @ORM\Column(name="attachmentsSize", type="float", nullable=true)
     */
    private $attachmentsSize;

    /**
     * @var string
     *
     * @ORM\Column(name="sender", type="string", length=255)
     */
    private $sender;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="mailStatsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @var array
     *
     * @ORM\Column(name="recipientEmails", type="json_array", nullable=true)
     */
    private $recipientEmails;

    /**
     * @var array
     *
     * @ORM\Column(name="rejectedEmails", type="json_array", nullable=true)
     */
    private $rejectedEmails;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->date->format('Y-m-d') . ' ' . $this->subject;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return MailStats
     */
    public function setDate(\DateTimeInterface $date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set nbEmail.
     *
     * @param int $nbEmail
     *
     * @return MailStats
     */
    public function setNbEmail($nbEmail)
    {
        $this->nbEmail = $nbEmail;

        return $this;
    }

    /**
     * Get nbEmail.
     *
     * @return int
     */
    public function getNbEmail()
    {
        return $this->nbEmail;
    }

    /**
     * Set nbOpenedEmail.
     *
     * @param int $nbOpenedEmail
     *
     * @return MailStats
     */
    public function setNbOpenedEmail($nbOpenedEmail)
    {
        $this->nbOpenedEmail = $nbOpenedEmail;

        return $this;
    }

    /**
     * Get nbOpenedEmail.
     *
     * @return int
     */
    public function getNbOpenedEmail()
    {
        return $this->nbOpenedEmail;
    }

    /**
     * Set subject.
     *
     * @param string $subject
     *
     * @return MailStats
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set attachmentsSize.
     *
     * @param float $attachmentsSize
     *
     * @return MailStats
     */
    public function setAttachmentsSize($attachmentsSize)
    {
        $this->attachmentsSize = $attachmentsSize;

        return $this;
    }

    /**
     * Get attachmentsSize.
     *
     * @return float
     */
    public function getAttachmentsSize()
    {
        return $this->attachmentsSize;
    }

    /**
     * Set sender.
     *
     * @param string $sender
     *
     * @return MailStats
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Get sender.
     *
     * @return string
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set createdBy.
     *
     *
     * @return MailStats
     */
    public function setCreatedBy(\PostparcBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return \PostparcBundle\Entity\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set token.
     *
     * @param string $token
     *
     * @return MailStats
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set recipientEmails.
     *
     * @param array $recipientEmails
     *
     * @return MailStats
     */
    public function setRecipientEmails($recipientEmails)
    {
        $this->recipientEmails = $recipientEmails;

        return $this;
    }

    /**
     * Get recipientEmails.
     *
     * @return array
     */
    public function getRecipientEmails()
    {
        return $this->recipientEmails;
    }

    /**
     * Set rejectedEmails.
     *
     * @param array $rejectedEmails
     *
     * @return MailStats
     */
    public function setRejectedEmails($rejectedEmails)
    {
        $this->rejectedEmails = $rejectedEmails;

        return $this;
    }

    /**
     * Get rejectedEmails.
     *
     * @return array
     */
    public function getRejectedEmails()
    {
        return $this->rejectedEmails;
    }

    /**
     * Set body.
     *
     * @param string|null $body
     *
     * @return MailStats
     */
    public function setBody($body = null)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     *
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }
}
