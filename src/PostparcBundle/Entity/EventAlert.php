<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityNameTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * EventAlert.
 *
 * @ORM\Table(name="event_alert")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\EventAlertRepository")
 * @Gedmo\Loggable
 */
class EventAlert
{
    use EntityTimestampableTrait;
    use EntityNameTrait;
    use EntityBlameableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="gap", type="integer")
     */
    private $gap = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="unit", type="string", length=5)
     */
    private $unit;

    /**
     * @var string
     *
     * @ORM\Column(name="direction", type="string", length=10)
     */
    private $direction;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="text", nullable=false)
     */
    private $message;

    /**
     * @var int
     *
     * @ORM\Column(name="recipients", type="integer")
     */
    private $recipients;

    /**
     * @var bool
     *
     * @ORM\Column(name="isPrintOnInterface", type="boolean")
     */
    private $isPrintOnInterface;

    /**
     * @var bool
     *
     * @ORM\Column(name="isManualAlert", type="boolean")
     */
    private $isManualAlert = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="addRGPDMessageForPerson", type="boolean")
     */
    private $addRGPDMessageForPerson = 1;

    /**
     * @var bool
     *
     * @ORM\Column(name="isSended", type="boolean")
     */
    private $isSended = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="isShowInInterfaceByOrganizator", type="boolean")
     */
    private $isShowInInterfaceByOrganizator = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="onlyForConfirmedContact", type="boolean")
     */
    private $onlyForConfirmedContact = 0;

    /**
     * @var bool
     *
     * @ORM\Column(name="onlyForUnConfirmedContact", type="boolean")
     */
    private $onlyForUnConfirmedContact = 0;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="limitToRecipiantsList", type="boolean")
     */
    private $limitToRecipiantsList = 0;

    /**
     * @var datetime
     *
     * @ORM\Column(name="effectiveDate", type="datetime", nullable=true)
     */
    private $effectiveDate;

    /**
     * @var datetime
     *
     * @ORM\Column(name="sendedDate", type="datetime", nullable=true)
     */
    private $sendedDate;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="eventAlertsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="eventAlertsUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Event", inversedBy="eventAlerts", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     * @Gedmo\Versioned
     */
    protected $event;

    /**
     * @ORM\ManyToOne(targetEntity="MailFooter", inversedBy="eventAlerts")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL", nullable=true)
     * @Gedmo\Versioned
     */
    protected $mailFooter;

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
     * @ORM\ManyToMany(targetEntity="Attachment", cascade={"remove", "persist"})
     * @ORM\JoinTable(name="eventAlerts_attachments")
     */
    private $attachments;

    /**
     * @var bool
     *
     * @ORM\Column(name="isSendedManualy", type="boolean")
     */
    private $isSendedManualy = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", length=128, nullable= true)
     */
    private $token;
    
    /**
     * @var string
     *
     * @ORM\Column(name="senderName", type="string", length=256, nullable= true)
     */
    private $senderName;
    
    /**
     * @var string
     *
     * @ORM\Column(name="senderEmail", type="string", length=256, nullable= true)
     */
    private $senderEmail;

    /**
     * @var int
     *
     * @ORM\Column(name="nbOpenedEmail", type="integer")
     */
    private $nbOpenedEmail = 0;
    
    
    /**
     * @ORM\ManyToMany(targetEntity="Pfo", inversedBy="eventAlerts")
     * @ORM\JoinTable(name="eventAlert_pfos")
     */
    protected $eventAlertPfos;

    /**
     * @ORM\ManyToMany(targetEntity="Representation", inversedBy="eventAlerts")
     * @ORM\JoinTable(name="eventAlert_representations")
     */
    protected $eventAlertRepresentations;
    
    /**
     * @ORM\ManyToMany(targetEntity="Person", inversedBy="eventAlerts")
     * @ORM\JoinTable(name="eventAlert_persons")
     */
    protected $eventAlertPersons;

    /**
     * @return string
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->attachments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->addRGPDMessageForPerson = true;
        $this->onlyForConfirmedContact = false;
        $this->onlyForUnConfirmedContact = false;
        $this->limitToRecipiantsList = false;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getAttachmentSizes()
    {
        return $this->fileSizeConvert($this->getAttachmentSizesValue());
    }
    
    public function getAttachmentSizesValue()
    {
        $size = 0;
        foreach ($this->getAttachments() as $attachment) {
            $size += $attachment->getAttachmentSize();
        }
        return $size;
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
     * Set gap.
     *
     * @param int $gap
     *
     * @return EventAlert
     */
    public function setGap($gap)
    {
        $this->gap = $gap;

        return $this;
    }

    /**
     * Get gap.
     *
     * @return int
     */
    public function getGap()
    {
        return $this->gap;
    }

    /**
     * Set unit.
     *
     * @param string $unit
     *
     * @return EventAlert
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit.
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set direction.
     *
     * @param string $direction
     *
     * @return EventAlert
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;

        return $this;
    }

    /**
     * Get direction.
     *
     * @return string
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Set message.
     *
     * @param string $message
     *
     * @return EventAlert
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set recipients.
     *
     * @param int $recipients
     *
     * @return EventAlert
     */
    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * Get recipients.
     *
     * @return int
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * Set isPrintOnInterface.
     *
     * @param bool $isPrintOnInterface
     *
     * @return EventAlert
     */
    public function setIsPrintOnInterface($isPrintOnInterface)
    {
        $this->isPrintOnInterface = $isPrintOnInterface;

        return $this;
    }

    /**
     * Get isPrintOnInterface.
     *
     * @return bool
     */
    public function getIsPrintOnInterface()
    {
        return $this->isPrintOnInterface;
    }

    /**
     * Set event.
     *
     * @param \PostparcBundle\Entity\Event|null $event
     *
     * @return EventAlert
     */
    public function setEvent(\PostparcBundle\Entity\Event $event = null)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * Get event.
     *
     * @return \PostparcBundle\Entity\Event|null
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * Set isSended.
     *
     * @param bool $isSended
     *
     * @return EventAlert
     */
    public function setIsSended($isSended)
    {
        $this->isSended = $isSended;

        return $this;
    }

    /**
     * Get isSended.
     *
     * @return bool
     */
    public function getIsSended()
    {
        return $this->isSended;
    }

    /**
     * Set effectiveDate.
     *
     * @param \DateTime|\DateTimeImmutable $effectiveDate
     *
     * @return EventAlert
     */
    public function setEffectiveDate(\DateTimeInterface $effectiveDate = null)
    {
        $this->effectiveDate = $effectiveDate;

        return $this;
    }

    /**
     * Get effectiveDate.
     *
     * @return \DateTime|null
     */
    public function getEffectiveDate()
    {
        return $this->effectiveDate;
    }

    /**
     * Set isShowInInterfaceByOrganizator.
     *
     * @param bool $isShowInInterfaceByOrganizator
     *
     * @return EventAlert
     */
    public function setIsShowInInterfaceByOrganizator($isShowInInterfaceByOrganizator)
    {
        $this->isShowInInterfaceByOrganizator = $isShowInInterfaceByOrganizator;

        return $this;
    }

    /**
     * Get isShowInInterfaceByOrganizator.
     *
     * @return bool
     */
    public function getIsShowInInterfaceByOrganizator()
    {
        return $this->isShowInInterfaceByOrganizator;
    }


    /**
     * Set onlyForConfirmedContact.
     *
     * @param bool $onlyForConfirmedContact
     *
     * @return EventAlert
     */
    public function setOnlyForConfirmedContact($onlyForConfirmedContact)
    {
        $this->onlyForConfirmedContact = $onlyForConfirmedContact;

        return $this;
    }

    /**
     * Get onlyForConfirmedContact.
     *
     * @return bool
     */
    public function getOnlyForConfirmedContact()
    {
        return $this->onlyForConfirmedContact;
    }

    /**
     * Set onlyForConfirmedContact.
     *
     * @param bool $onlyForUnConfirmedContact
     *
     * @return EventAlert
     */
    public function setOnlyForUnConfirmedContact($onlyForUnConfirmedContact)
    {
        $this->onlyForUnConfirmedContact = $onlyForUnConfirmedContact;

        return $this;
    }

    /**
     * Get onlyForUnConfirmedContact.
     *
     * @return bool
     */
    public function getOnlyForUnConfirmedContact()
    {
        return $this->onlyForUnConfirmedContact;
    }
    
    
    /**
     * Set limitToRecipiantsList.
     *
     * @param bool $limitToRecipiantsList
     *
     * @return EventAlert
     */
    public function setLimitToRecipiantsList($limitToRecipiantsList)
    {
        $this->limitToRecipiantsList = $limitToRecipiantsList;

        return $this;
    }

    /**
     * Get limitToRecipiantsList.
     *
     * @return bool
     */
    public function getLimitToRecipiantsList()
    {
        return $this->limitToRecipiantsList;
    }

    /**
     * Set sendedDate.
     *
     * @param \DateTime|\DateTimeImmutable $sendedDate
     *
     * @return EventAlert
     */
    public function setSendedDate(\DateTimeInterface $sendedDate = null)
    {
        $this->sendedDate = $sendedDate;

        return $this;
    }

    /**
     * Get sendedDate.
     *
     * @return \DateTime|null
     */
    public function getSendedDate()
    {
        return $this->sendedDate;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return EventAlert
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return EventAlert
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set mailFooter.
     *
     * @param \PostparcBundle\Entity\MailFooter|null $mailFooter
     *
     * @return EventAlert
     */
    public function setMailFooter(\PostparcBundle\Entity\MailFooter $mailFooter = null)
    {
        $this->mailFooter = $mailFooter;

        return $this;
    }

    /**
     * Get mailFooter.
     *
     * @return \PostparcBundle\Entity\MailFooter|null
     */
    public function getMailFooter()
    {
        return $this->mailFooter;
    }

    /**
     * Set recipientEmails.
     *
     * @param array $recipientEmails
     *
     * @return EventAlert
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
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return EventAlert
     */
    public function setCreated(\DateTimeInterface $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Set updated.
     *
     * @param \DateTime $updated
     *
     * @return EventAlert
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Set rejectedEmails.
     *
     * @param array $rejectedEmails
     *
     * @return EventAlert
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
     * Add attachment.
     *
     *
     * @return EventAlert
     */
    public function addAttachment(\PostparcBundle\Entity\Attachment $attachment)
    {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * Remove attachment.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeAttachment(\PostparcBundle\Entity\Attachment $attachment)
    {
        return $this->attachments->removeElement($attachment);
    }

    /**
     * Get attachments.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * Set isSendedManualy.
     *
     * @param bool $isSendedManualy
     *
     * @return EventAlert
     */
    public function setIsSendedManualy($isSendedManualy)
    {
        $this->isSendedManualy = $isSendedManualy;

        return $this;
    }

    /**
     * Get isSendedManualy.
     *
     * @return bool
     */
    public function getIsSendedManualy()
    {
        return $this->isSendedManualy;
    }

    /**
     * Set token.
     *
     * @param string $token
     *
     * @return EventAlert
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
     * Set isManualAlert.
     *
     * @param bool $isManualAlert
     *
     * @return EventAlert
     */
    public function setIsManualAlert($isManualAlert)
    {
        $this->isManualAlert = $isManualAlert;

        return $this;
    }

    /**
     * Get isManualAlert.
     *
     * @return bool
     */
    public function getIsManualAlert()
    {
        return $this->isManualAlert;
    }

    /**
     * Set nbOpenedEmail.
     *
     * @param int $nbOpenedEmail
     *
     * @return EventAlert
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
     * Set addRGPDMessageForPerson.
     *
     * @param bool $addRGPDMessageForPerson
     *
     * @return EventAlert
     */
    public function setAddRGPDMessageForPerson($addRGPDMessageForPerson)
    {
        $this->addRGPDMessageForPerson = $addRGPDMessageForPerson;

        return $this;
    }

    /**
     * Get addRGPDMessageForPerson.
     *
     * @return bool
     */
    public function getAddRGPDMessageForPerson()
    {
        return $this->addRGPDMessageForPerson;
    }

    /**
     * @param type $bytes
     *
     * @return string
     */
    private function fileSizeConvert($bytes)
    {
        $result = null;
        $bytes = floatval($bytes);
        $arBytes = [
          0 => [
              'UNIT' => 'TB',
              'VALUE' => pow(1024, 4),
          ],
          1 => [
              'UNIT' => 'GB',
              'VALUE' => pow(1024, 3),
          ],
          2 => [
              'UNIT' => 'MB',
              'VALUE' => pow(1024, 2),
          ],
          3 => [
              'UNIT' => 'KB',
              'VALUE' => 1024,
          ],
          4 => [
              'UNIT' => 'B',
              'VALUE' => 1,
          ],
        ];

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem['VALUE']) {
                $result = $bytes / $arItem['VALUE'];
                $result = str_replace('.', ',', strval(round($result, 2))) . ' ' . $arItem['UNIT'];
                break;
            }
        }

        return $result;
    }

    /**
     * Set senderName.
     *
     * @param string|null $senderName
     *
     * @return EventAlert
     */
    public function setSenderName($senderName = null)
    {
        $this->senderName = $senderName;

        return $this;
    }

    /**
     * Get senderName.
     *
     * @return string|null
     */
    public function getSenderName()
    {
        return $this->senderName;
    }

    /**
     * Set senderEmail.
     *
     * @param string|null $senderEmail
     *
     * @return EventAlert
     */
    public function setSenderEmail($senderEmail = null)
    {
        $this->senderEmail = $senderEmail;

        return $this;
    }

    /**
     * Get senderEmail.
     *
     * @return string|null
     */
    public function getSenderEmail()
    {
        return $this->senderEmail;
    }

    /**
     * Add eventAlertPfo.
     *
     * @param \PostparcBundle\Entity\Pfo $eventAlertPfo
     *
     * @return EventAlert
     */
    public function addEventAlertPfo(\PostparcBundle\Entity\Pfo $eventAlertPfo)
    {
        $this->eventAlertPfos[] = $eventAlertPfo;

        return $this;
    }

    /**
     * Remove eventAlertPfo.
     *
     * @param \PostparcBundle\Entity\Pfo $eventAlertPfo
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEventAlertPfo(\PostparcBundle\Entity\Pfo $eventAlertPfo)
    {
        return $this->eventAlertPfos->removeElement($eventAlertPfo);
    }

    /**
     * Get eventAlertPfos.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventAlertPfos()
    {
        return $this->eventAlertPfos;
    }

    /**
     * Add eventAlertRepresentation.
     *
     * @param \PostparcBundle\Entity\Representation $eventAlertRepresentation
     *
     * @return EventAlert
     */
    public function addEventAlertRepresentation(\PostparcBundle\Entity\Representation $eventAlertRepresentation)
    {
        $this->eventAlertRepresentations[] = $eventAlertRepresentation;

        return $this;
    }

    /**
     * Remove eventAlertRepresentation.
     *
     * @param \PostparcBundle\Entity\Representation $eventAlertRepresentation
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEventAlertRepresentation(\PostparcBundle\Entity\Representation $eventAlertRepresentation)
    {
        return $this->eventAlertRepresentations->removeElement($eventAlertRepresentation);
    }

    /**
     * Get eventAlertRepresentations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventAlertRepresentations()
    {
        return $this->eventAlertRepresentations;
    }

    /**
     * Add eventAlertPerson.
     *
     * @param \PostparcBundle\Entity\Person $eventAlertPerson
     *
     * @return EventAlert
     */
    public function addEventAlertPerson(\PostparcBundle\Entity\Person $eventAlertPerson)
    {
        $this->eventAlertPersons[] = $eventAlertPerson;

        return $this;
    }

    /**
     * Remove eventAlertPerson.
     *
     * @param \PostparcBundle\Entity\Person $eventAlertPerson
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeEventAlertPerson(\PostparcBundle\Entity\Person $eventAlertPerson)
    {
        return $this->eventAlertPersons->removeElement($eventAlertPerson);
    }

    /**
     * Get eventAlertPersons.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventAlertPersons()
    {
        return $this->eventAlertPersons;
    }

    /**
     * Get nbEmail.
     *
     * @return int
     */
    public function getNbEmail()
    {
        return count($this->recipientEmails);
    }
}
