<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityNameTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * Event.
 *
 * @ORM\Table(name="event", indexes={@ORM\Index(name="event_slugs", columns={"slug"})})
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\EventRepository")
 * @Gedmo\Loggable
 * @ORM\HasLifecycleCallbacks
 */
class Event
{
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    public $pfos;
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    public $persons;
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    public $representations;
    /**
     * @var \PostparcBundle\Entity\Organization|null|mixed
     */
    public $organization;
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
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     * @Gedmo\Versioned
     */
    private $date;

    /**
     * @var string
     *
     * @ORM\Column(name="duration", type="string", length=25, nullable=true)
     * @Gedmo\Versioned
     */
    private $duration = 'P0DT0H0M';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endDate", type="datetime", nullable=true)
     */
    private $endDate;

    /**
     * @var int|null
     *
     * @ORM\Column(name="nbPlace", type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    private $nbPlace;

    /**
     * @var string
     * @Assert\File( maxSize = "1024k", mimeTypes={"image/png","image/jpeg"}, mimeTypesMessage = "restrictionPngJpegFormat")
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var int
     *
     * @ORM\Column(name="frequency", type="smallint")
     */
    protected $frequency;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_shared", type="boolean", options={"default" = "0"})
     * @Gedmo\Versioned
     */
    protected $isShared;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_editable_by_other_entities", type="boolean", options={"default" = "0"})
     * @Gedmo\Versioned
     */
    protected $isEditableByOtherEntities = false;

    /**
     * @ORM\ManyToOne(targetEntity="EventType", inversedBy="events")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $eventType;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="eventsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="eventsUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\ManyToMany(targetEntity="User" , inversedBy="eventsOrganizator")
     * @ORM\JoinTable(name="event_organizators")
     */
    private $organizators;

    /**
     * @ORM\ManyToMany(targetEntity="Organization" , inversedBy="events")
     * @ORM\JoinTable(name="event_organizations")
     */
    private $organizations;

    /**
     * @var string
     *
     * @ORM\Column(name="env", type="string", length=50)
     */
    private $env;

    /**
     * @ORM\OneToOne(targetEntity="Coordinate", inversedBy="event",  cascade={"remove", "persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $coordinate;

    /**
     * @ORM\ManyToMany(targetEntity="Tag" , inversedBy="events")
     * @ORM\JoinTable(name="event_tags")
     */
    private $tags;

    /**
     * @ORM\OneToMany(targetEntity="EventPfos", mappedBy="event",
     * cascade={"persist", "remove"})
     */
    protected $eventPfos;

    /**
     * @ORM\OneToMany(targetEntity="EventRepresentations", mappedBy="event",
     * cascade={"persist", "remove"})
     */
    protected $eventRepresentations;


    /**
     * @ORM\OneToMany(targetEntity="EventAlert", mappedBy="event", cascade={"persist"})
     */
    private $eventAlerts;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="events")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;

    /**
     * @ORM\OneToMany(targetEntity="EventPersons", mappedBy="event",
     * cascade={"persist", "remove"})
     */
    protected $eventPersons;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pfos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->persons = new \Doctrine\Common\Collections\ArrayCollection();
        $this->representations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->eventAlerts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return type
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
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
     * Set description.
     *
     * @param string $description
     *
     * @return Event
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set date.
     *
     * @param \DateTime $date
     *
     * @return Event
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
     * Set nbPlace.
     *
     * @param int|null $nbPlace
     *
     * @return Event
     */
    public function setNbPlace($nbPlace = null)
    {
        $this->nbPlace = $nbPlace;

        return $this;
    }

    /**
     * Get nbPlace.
     *
     * @return int|null
     */
    public function getNbPlace()
    {
        return $this->nbPlace;
    }

    /**
     * Set image.
     *
     * @param string|null $image
     *
     * @return Event
     */
    public function setImage($image = null)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set env.
     *
     * @param string $env
     *
     * @return Event
     */
    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Get env.
     *
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Set eventType.
     *
     * @param \PostparcBundle\Entity\EventType|null $eventType
     *
     * @return Event
     */
    public function setEventType(\PostparcBundle\Entity\EventType $eventType = null)
    {
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * Get eventType.
     *
     * @return \PostparcBundle\Entity\EventType|null
     */
    public function getEventType()
    {
        return $this->eventType;
    }

    /**
     * Set coordinate.
     *
     * @param \PostparcBundle\Entity\Coordinate|null $coordinate
     *
     * @return Event
     */
    public function setCoordinate(\PostparcBundle\Entity\Coordinate $coordinate = null)
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * Get coordinate.
     *
     * @return \PostparcBundle\Entity\Coordinate|null
     */
    public function getCoordinate()
    {
        return $this->coordinate;
    }

    /**
     * Add tag.
     *
     *
     * @return Event
     */
    public function addTag(\PostparcBundle\Entity\Tag $tag)
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTag(\PostparcBundle\Entity\Tag $tag)
    {
        return $this->tags->removeElement($tag);
    }

    /**
     * Get tags.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set frequency.
     *
     * @param int $frequency
     *
     * @return Event
     */
    public function setFrequency($frequency)
    {
        $this->frequency = $frequency;

        return $this;
    }

    /**
     * Get frequency.
     *
     * @return int
     */
    public function getFrequency()
    {
        return $this->frequency;
    }

    /**
     * Set organization.
     *
     * @param \PostparcBundle\Entity\Organization|null $organization
     *
     * @return Event
     */
    public function setOrganization(\PostparcBundle\Entity\Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization.
     *
     * @return \PostparcBundle\Entity\Organization|null
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Get pfos.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfos()
    {
        $pfos = [];
        foreach ($this->getEventPfos() as $eventPfo) {
            $pfos[] = $eventPfo->getPfo();
        }

        return $pfos;
    }

    /**
     * Get persons.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersons()
    {
        $persons = [];
        foreach ($this->getEventPersons() as $eventPerson) {
            $persons[] = $eventPerson->getPerson();
        }

        return $persons;
    }

    /**
     * Get representations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRepresentations()
    {
        $representations = [];
        foreach ($this->getEventRepresentations() as $eventRepresentation) {
            $representations[] = $eventRepresentation->getRepresentation();
        }

        return $representations;
    }

    /**
     * Add eventAlert.
     *
     *
     * @return Event
     */
    public function addEventAlert(\PostparcBundle\Entity\EventAlert $eventAlert)
    {
        $this->eventAlerts[] = $eventAlert;

        return $this;
    }

    /**
     * Remove eventAlert.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventAlert(\PostparcBundle\Entity\EventAlert $eventAlert)
    {
        return $this->eventAlerts->removeElement($eventAlert);
    }

    /**
     * Get eventAlerts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventAlerts()
    {
        return $this->eventAlerts;
    }

    /**
     * Set isShared.
     *
     * @param bool $isShared
     *
     * @return Event
     */
    public function setIsShared($isShared)
    {
        $this->isShared = $isShared;

        return $this;
    }

    /**
     * Get isShared.
     *
     * @return bool
     */
    public function getIsShared()
    {
        return $this->isShared;
    }

    /**
     * Set entity.
     *
     * @param \PostparcBundle\Entity\Entity|null $entity
     *
     * @return Event
     */
    public function setEntity(\PostparcBundle\Entity\Entity $entity = null)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity.
     *
     * @return \PostparcBundle\Entity\Entity|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * *******************   SPECIALS METHODS FOR UPLOAD FILE *********************.
     */

    /**
     * @return type
     */
    public function getFullImagePath()
    {
        return null === $this->image ? null : $this->getUploadRootDir() . $this->image;
    }

    /**
     * @return type
     */
    public function getwebPath()
    {
        return null === $this->image ? null : 'uploads/eventImages/' . $this->env . '/' . $this->id . '/' . $this->image;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadImage()
    {
        // the file property can be empty if the field is not required
        if (null === $this->image) {
            return;
        }
        if ($this->id === 0) {
            $this->image->move($this->getTmpUploadRootDir(), $this->image->getClientOriginalName());
        } else {
            // cas particulier
            if (is_string($this->image)) {
                return;
            }
            $this->image->move($this->getUploadRootDir(), $this->image->getClientOriginalName());
        }
        $this->setImage($this->image->getClientOriginalName());
    }

    /**
     * @ORM\PostPersist()
     */
    public function moveImage()
    {
        if (null === $this->image) {
            return;
        }
        if (!is_dir($this->getUploadRootDir())) {
            mkdir($this->getUploadRootDir());
        }
        copy($this->getTmpUploadRootDir() . $this->image, $this->getFullImagePath());
        unlink($this->getTmpUploadRootDir() . $this->image);
    }

    /**
     * @ORM\PreRemove()
     */
    public function removeImage()
    {
        if (file_exists($this->getFullImagePath())) {
            unlink($this->getFullImagePath());
            rmdir($this->getUploadRootDir());
        }
    }

    /**
     * @return type
     */
    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir() . $this->getId() . '/';
    }

    /**
     * @return type
     */
    protected function getTmpUploadRootDir()
    {
        // the absolute directory path where uploaded documents should be saved
        $dir = __DIR__ . '/../../../web/uploads/eventImages/' . $this->env;
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        return $dir . '/';
    }

    /**
     * Add organizator.
     *
     *
     * @return Event
     */
    public function addOrganizator(\PostparcBundle\Entity\User $organizator)
    {
        $this->organizators[] = $organizator;

        return $this;
    }

    /**
     * Remove organizator.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeOrganizator(\PostparcBundle\Entity\User $organizator)
    {
        return $this->organizators->removeElement($organizator);
    }

    /**
     * Get organizators.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizators()
    {
        return $this->organizators;
    }

    /**
     * Add organization.
     *
     *
     * @return Event
     */
    public function addOrganization(\PostparcBundle\Entity\Organization $organization)
    {
        $this->organizations[] = $organization;

        return $this;
    }

    /**
     * Remove organization.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeOrganization(\PostparcBundle\Entity\Organization $organization)
    {
        return $this->organizations->removeElement($organization);
    }

    /**
     * Get organizations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * Set isEditableByOtherEntities.
     *
     * @param bool $isEditableByOtherEntities
     *
     * @return Event
     */
    public function setIsEditableByOtherEntities($isEditableByOtherEntities)
    {
        $this->isEditableByOtherEntities = $isEditableByOtherEntities;

        return $this;
    }

    /**
     * Get isEditableByOtherEntities.
     *
     * @return bool
     */
    public function getIsEditableByOtherEntities()
    {
        return $this->isEditableByOtherEntities;
    }

    /**
     * Set duration.
     *
     * @param string $duration
     *
     * @return Event
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration.
     *
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set endDate.
     *
     * @param \DateTime $endDate
     *
     * @return Event
     */
    public function setEndDate(\DateTimeInterface $endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return Event
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
     * @return Event
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Add eventPerson.
     *
     *
     * @return Event
     */
    public function addEventPerson(\PostparcBundle\Entity\EventPersons $eventPerson)
    {
        $this->eventPersons[] = $eventPerson;

        return $this;
    }

    /**
     * Remove eventPerson.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventPerson(\PostparcBundle\Entity\EventPersons $eventPerson)
    {
        return $this->eventPersons->removeElement($eventPerson);
    }

    /**
     * Get eventPersons.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventPersons()
    {
        return $this->eventPersons;
    }

    /**
     * Add eventPfo.
     *
     *
     * @return Event
     */
    public function addEventPfo(\PostparcBundle\Entity\EventPfos $eventPfo)
    {
        $this->eventPfos[] = $eventPfo;

        return $this;
    }

    /**
     * Remove eventPfo.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventPfo(\PostparcBundle\Entity\EventPfos $eventPfo)
    {
        return $this->eventPfos->removeElement($eventPfo);
    }

    /**
     * Get eventPfos.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventPfos()
    {
        return $this->eventPfos;
    }

    /**
     * Add eventRepresentation.
     *
     *
     * @return Event
     */
    public function addEventRepresentation(\PostparcBundle\Entity\EventRepresentations $eventRepresentation)
    {
        $this->eventRepresentations[] = $eventRepresentation;

        return $this;
    }

    /**
     * Remove eventRepresentation.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventRepresentation(\PostparcBundle\Entity\EventRepresentations $eventRepresentation)
    {
        return $this->eventRepresentations->removeElement($eventRepresentation);
    }

    /**
     * Get eventRepresentations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventRepresentations()
    {
        return $this->eventRepresentations;
    }
    
    public function getNbParticipant()
    {
        
        $nbParticipants = 0;
        $personIds = [];
        
        $nbParticipants = count($this->eventPersons);
        $personIds = array_map(function ($eventPerson){
            /*@var $eventPerson eventPersons*/
            return $eventPerson->getPerson()->getId();
            
        },$this->eventPersons->toArray());
        
        $eventPfos = $this->eventPfos;
        foreach ($eventPfos as $eventPfo)
        {   
            if($eventPfo->getPfo()->getPerson()) {
                $personId = $eventPfo->getPfo()->getPerson()->getId();
                if (!in_array($personId, $personIds)){
                    $nbParticipants++;
                    $personIds[] = $eventPfo->getPfo()->getPerson()->getId();
                }
            }
        }

        $eventRepresentations = $this->eventRepresentations;
        foreach ($eventRepresentations as $eventRepresentation)
        {
            /*@var $eventRepresentation EventRepresentations */
            if($eventRepresentation->getRepresentation()->getPerson()) {
                $personId = $eventRepresentation->getRepresentation()->getPerson()->getId();
                if (!in_array($personId, $personIds)){                
                     if ($eventRepresentation->getConfirmationDate()){
                        $nbParticipants++;
                        $personIds[] = $personId = $eventRepresentation->getRepresentation()->getPerson()->getId();
                     }
                }
            }
        } 
        

        return $nbParticipants;
    }
    
    public function getNbPresent()
    {
        $nbPresent = 0;
        $presentPersonIds = [];
        
        $eventPersons = $this->eventPersons;
        foreach ($eventPersons as $eventPerson)
        {            
            if ($eventPerson->getConfirmationDate() || $eventPerson->getRepresentedByDate()){
                $nbPresent++;
                $presentPersonIds[] = $eventPerson->getPerson()->getId();
            }                        
        }
        
        $eventPfos = $this->eventPfos;
        foreach ($eventPfos as $eventPfo)
        {
            if($eventPfo->getPfo()->getPerson()) {
                $personId = $eventPfo->getPfo()->getPerson()->getId();
                if (!in_array($personId, $presentPersonIds)){                
                     if ($eventPfo->getConfirmationDate() || $eventPfo->getRepresentedByDate()){
                        $nbPresent++;
                        $presentPersonIds[] = $eventPfo->getPfo()->getPerson()->getId();
                     }
                }
            }
        }
        
        $eventRepresentations = $this->eventRepresentations;
        foreach ($eventRepresentations as $eventRepresentation)
        {
            /*@var $eventRepresentation EventRepresentations */
            if($eventRepresentation->getRepresentation()->getPerson()) {
                $personId = $eventRepresentation->getRepresentation()->getPerson()->getId();
                if (!in_array($personId, $presentPersonIds)){                
                     if ($eventRepresentation->getConfirmationDate() || $eventRepresentation->getRepresentedByDate()){
                        $nbPresent++;
                        $presentPersonIds[] = $personId = $eventRepresentation->getRepresentation()->getPerson()->getId();
                     }
                }
            }
        }        

        return $nbPresent;
    }
    
    public function getNbRepresentedBy()
    {
        $nb = 0;
        $personIds = [];
        
        $eventPersons = $this->eventPersons;
        foreach ($eventPersons as $eventPerson)
        {            
            if ($eventPerson->getRepresentedByDate()){
                $nb++;
                $personIds[] = $eventPerson->getPerson()->getId();
            }                        
        }
        
        $eventPfos = $this->eventPfos;
        foreach ($eventPfos as $eventPfo)
        {
            if($eventPfo->getPfo()->getPerson()) {
                $personId = $eventPfo->getPfo()->getPerson()->getId();
                if (!in_array($personId, $personIds)){
                     if ($eventPfo->getRepresentedByDate()){
                        $nb++;
                        $personIds[] = $eventPfo->getPfo()->getPerson()->getId();
                     }
                }
            }
        }
        
        $eventRepresentations = $this->eventRepresentations;
        foreach ($eventRepresentations as $eventRepresentation)
        {
            /*@var $eventRepresentation EventRepresentations */
            if($eventRepresentation->getRepresentation()->getPerson()) {
                $personId = $eventRepresentation->getRepresentation()->getPerson()->getId();
                if (!in_array($personId, $personIds)){
                     if ($eventRepresentation->getRepresentedByDate()){
                        $nb++;
                        $personIds[] = $eventRepresentation->getRepresentation()->getPerson()->getId();
                     }
                }
            }
        }        

        return $nb;
    }
    
    
    public function getNbAbsent()
    {
        $nbAbsent = 0;
        $absentPersonIds = [];
        
        $eventPersons = $this->eventPersons;
        foreach ($eventPersons as $eventPerson)
        {            
            if ($eventPerson->getUnconfirmationDate()){
                $nbAbsent++;
                $absentPersonIds[] = $eventPerson->getPerson()->getId();
            }                        
        }
        
        $eventPfos = $this->eventPfos;
        foreach ($eventPfos as $eventPfo)
        {
            if($eventPfo->getPfo()->getPerson()) {
                $personId = $eventPfo->getPfo()->getPerson()->getId();
                if (!in_array($personId, $absentPersonIds)){
                     if ($eventPfo->getUnconfirmationDate()){
                        $nbAbsent++;
                     }
                }
            }
        }
        
        $eventRepresentations = $this->eventRepresentations;
        foreach ($eventRepresentations as $eventRepresentation)
        {
            /*@var $eventRepresentation EventRepresentations */
            if($eventRepresentation->getRepresentation()->getPerson()) {
                $personId = $eventRepresentation->getRepresentation()->getPerson()->getId();
                if (!in_array($personId, $absentPersonIds)){                
                     if ($eventRepresentation->getUnconfirmationDate()){
                        $nbAbsent++;
                        $absentPersonIds[] = $personId = $eventRepresentation->getRepresentation()->getPerson()->getId();
                     }
                }
            }
        }           
        
        return $nbAbsent;
    }
    
}
