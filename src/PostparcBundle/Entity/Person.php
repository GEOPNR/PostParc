<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JeroenDesloovere\VCard\VCard;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;
use PostparcBundle\Entity\Traits\EntitySoftDeletableTrait;
use PostparcBundle\Entity\Traits\EntityLockableTrait;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\PersonRepository")
 * @ORM\Table(name="person", indexes={@ORM\Index(name="person_slugs", columns={"slug"})})
 * @Gedmo\Loggable
 * @ORM\HasLifecycleCallbacks
 */
class Person {

    use EntityTimestampableTrait;
    use EntityBlameableTrait;
    use EntitySoftDeletableTrait;
    use EntityLockableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Gedmo\Slug(fields={"name","firstName"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $firstName;

    /**
     * @ORM\ManyToOne(targetEntity="Coordinate", inversedBy="persons", cascade={"remove", "persist"})
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $coordinate;

    /**
     * @ORM\ManyToOne(targetEntity="Civility", inversedBy="persons", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id")
     * @Gedmo\Versioned
     */
    protected $civility;

    /**
     * @var text
     *
     * @ORM\Column(name="observation", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $observation;

    /**
     * @ORM\Column(name="birthDate", type="datetime", nullable=true)
     */
    private $birthDate;

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
     * @ORM\Column(name="nb_minor_childreen", type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    protected $nbMinorChildreen;

    /**
     * @ORM\Column(name="nb_major_childreen", type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    protected $nbMajorChildreen;

    /**
     * @var bool
     *
     * @ORM\Column(name="dont_want_to_be_contacted", type="boolean", options={"default" = "0"})
     * @Gedmo\Versioned
     */
    protected $dontWantToBeContacted = false;

    /**
     * @var string
     * @Assert\File( maxSize = "1024k", mimeTypes={"image/png","image/jpeg"}, mimeTypesMessage = "restrictionPngJpegFormat")
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    protected $image;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="civilitiesCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="civilitiesUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @var string
     *
     * @ORM\Column(name="env", type="string", length=50)
     */
    private $env;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="person", cascade={ "persist"})
     */
    private $pfos;

    /**
     * @ORM\ManyToMany(targetEntity="Email", inversedBy="personsPreferedEmails")
     * @ORM\JoinTable(name="person_prefered_emails")
     */
    private $preferedEmails;

    /**
     * @ORM\OneToMany(targetEntity="PfoPersonGroup", mappedBy="person", cascade={"persist"})
     */
    protected $pfoPersonGroups;

    /**
     * @ORM\ManyToOne(targetEntity="Profession", inversedBy="persons", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $profession;

    /**
     * @ORM\ManyToOne(targetEntity="City", inversedBy="personsBirthIn", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $birthLocation;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="person", cascade={"remove","persist"})
     */
    protected $representations;

    /**
     * @ORM\ManyToMany(targetEntity="Tag" , inversedBy="persons")
     * @ORM\JoinTable(name="person_tags")
     */
    private $tags;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="persons")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;

    /**
     * @ORM\OneToMany(targetEntity="EventPersons", mappedBy="person",
     * cascade={"persist", "remove"})
     */
    protected $eventPersons;

    /**
     * @var bool
     *
     * @ORM\Column(name="dont_show_coordinate_for_readers", type="boolean", options={"default" = "0"})
     */
    protected $dontShowCoordinateForReaders = false;
    
    /**
     * @ORM\ManyToMany(targetEntity="EventAlert", mappedBy="eventAlertPersons")
     */
    protected $eventAlerts;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->pfos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->preferedEmails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pfoPersonGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->eventPersons = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString() {
        return ucfirst($this->name) . ' ' . $this->firstName;
    }

    public function getApiFormated($format = 'object') {
        $formated = [
            'id' => $this->id,
            'name' => $this->name,
            'firstName' => $this->firstName,
            'civility' => $this->getCivility() ? $this->getCivility()->getName() : null,
            'slug' => $this->slug,
            'coordinate' => ( $this->coordinate && !$this->getDontWantToBeContacted() ) ? $this->coordinate->getApiFormated($format) : null,
        ];
        
        $tags = [];
        foreach ($this->getTags() as $tag) {
            if($tag){
                $tags[] = [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                    'slug' => $tag->getSlug()
                ];
            }
        }
        $formated['tags'] = $tags;

        $groups = [];
        foreach ($this->getPfoPersonGroups() as $pfoPersonGroup) {
            $group = $pfoPersonGroup->getGroup();
            if($group){
                $groups[] = [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'slug' => $group->getSlug()
                ];
            }
        }
        $formated['groups'] = $groups;

        if ('object' == $format) {
            $formated = array_merge(
                    $formated,
                    [
                        'civility' => $this->getCivility() ? $this->getCivility()->getName() : null,
                        'coordinate' => $this->coordinate->getApiFormated(),
                        'profession' => $this->getProfession() !== null ? $this->getProfession()->getName() : null,
                        'birthLocation' => $this->getBirthLocation() ? $this->getBirthLocation()->getName() : null,
                        'birthDate' => $this->getBirthDate(),
                        'obervation' => $this->observation,
                    ]
            );
            $pfos = [];
            foreach ($this->getPfos() as $pfo) {
                $pfos[] = $pfo->getApiFormated('list');
            }
            $formated['pfos'] = $pfos;
        }

        return $formated;
    }

    public function getCoordinateStringForDuplicateSearch() {
        $coord = $this->__toString();

        $coordinate = $this->getCoordinate();
        if ($coordinate) {
            $coord .= $coordinate->__toString();
        }

        return $coord;
    }

    /**
     * Set deletedAt.
     *
     * @param \DateTime $deletedAt
     *
     * @return Plan
     */
    public function setDeletedAt(\DateTimeInterface $deletedAt) {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt.
     *
     * @return \DateTime
     */
    public function getDeletedAt() {
        return $this->deletedAt;
    }

    /**
     * @return object
     */
    public function getCity() {
        $city = null;
        if ($this->getCoordinate()) {
            $city = $this->getCoordinate()->getCity();
        }

        return $city;
    }

    /**
     * @return string
     */
    public function getSexe() {
        $sexe = 'male';
        if ($this->civility && $this->civility->getIsFeminine()) {
            $sexe = 'female';
        }

        return $sexe;
    }

    /**
     * @return string
     */
    public function getClassName() {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * @param Request $postRequest
     * @param bool    $fromPfo
     *
     * @return string
     */
    public function getPrintForSticker($postRequest, $user, $fromPfo = 0, $personnalFieldsRestriction = []) {
        $content = '';
        $separator = '';

        if ($postRequest->has('person')) {
            $tabFields = $postRequest->get('person');
            if (isset($tabFields['civility'])) {
                $content .= $separator . $this->getCivility();
                $separator = ' ';
            }
            if (isset($tabFields['lastName'])) {
                $content .= $separator . trim($this->getName());
                $separator = ' ';
            }
            if (isset($tabFields['firstName'])) {
                $content .= $separator . trim($this->getFirstName());
                $separator = ' ';
            }
            if (isset($tabFields['coordinate']) && !$fromPfo && $this->getCoordinate() && (($user->hasRole('ROLE_CONTRIBUTOR') || $user->hasRole('ROLE_CONTRIBUTOR_PLUS') || $user->hasRole('ROLE_ADMIN')) || !$this->getDontShowCoordinateForReaders())) {
                $coordinate = $this->getCoordinate();
                $content .= $coordinate->getPrintForSticker($postRequest, $user, $fromPfo, $personnalFieldsRestriction);
            }
        }

        return nl2br($content);
    }

    /**
     * generateVcardContent.
     *
     * @param Person $person
     *
     * @return type
     */
    public function generateVcardContent($personnalFieldsRestriction = []) {
        $vcard = new VCard();

        $lastname = $this->getLastName();
        $firstname = $this->getFirstName();
        $additional = '';
        $prefix = '';
        $suffix = '';
        // add personal data
        $vcard->addName($lastname, $firstname, $additional, $prefix, $suffix);

        if ($this->getBirthDate() && !$this->getDontWantToBeContacted() && !in_array('birthDate', $personnalFieldsRestriction)) {
            $vcard->addBirthday($this->getBirthDate()->format('Y-m-d'));
        }

        // add coordinate data
        $coordinate = ($this->getCoordinate() && !$this->getDontWantToBeContacted()) ? $this->getCoordinate() : null;

        if ($coordinate) {
            $address1 = in_array('addressLine1', $personnalFieldsRestriction) ? '' : $coordinate->getAddressLine1();
            $address2 = in_array('addressLine2', $personnalFieldsRestriction) ? '' : $coordinate->getAddressLine2();
            $city = in_array('city', $personnalFieldsRestriction) ? null : $coordinate->getCity();
            $vcard->addAddress(null, null, $address1 . ' ' . $address2, $city, null, $city !== null ? $city->getZipCode() : '', $city !== null ? $city->getCountry() : '', 'HOME');
            if (!in_array('email', $personnalFieldsRestriction)) {
                $vcard->addEmail($coordinate->getEmail(), 'HOME');
            }
            if (!in_array('phone', $personnalFieldsRestriction)) {
                $vcard->addPhoneNumber($coordinate->getPhone(), 'HOME');
            }
            if (!in_array('mobilePhone', $personnalFieldsRestriction)) {
                $vcard->addPhoneNumber($coordinate->getMobilePhone(), 'CELL');
            }
            if (!in_array('fax', $personnalFieldsRestriction)) {
                $vcard->addPhoneNumber($coordinate->getFax(), 'FAX');
            }
            if (!in_array('webSite', $personnalFieldsRestriction)) {
                $vcard->addURL($coordinate->getWebSite(), 'HOME');
            }
        }
        
        // image
        if($this->getImage()){
            $url = __DIR__ . '/../../../web/'.$this->getwebPath();
            $vcard->addPhoto($url);
        }
        
        // tags
        $tags = [];
        foreach($this->getTags() as $tag) {
            $tags[] = $tag->__toString();
        }
        if(count($tags)) {
            $vcard->addCategories($tags);
        }
        
        // add postparc note
        $vcard->addNote('vcard generated by postparc');
        
        
        // response
        return $vcard->getOutput();
    }

    public function getScalarInfos() {
        return [
            'object' => $this,
            'p_id' => $this->id,
            'p_firstName' => $this->firstName,
            'p_name' => $this->name,
            'p_civility' => $this->getCivility(),
            'coord.addressLine1' => ($this->getCoordinate() && !$this->getDontWantToBeContacted()) ? $this->getCoordinate()->getAddressLine1() : '',
            'coord.addressLine2' => ($this->getCoordinate() && !$this->getDontWantToBeContacted()) ? $this->getCoordinate()->getAddressLine2() : '',
            'coord.cedex' => ($this->getCoordinate() && !$this->getDontWantToBeContacted()) ? $this->getCoordinate()->getCedex() : '',
            'cityName' => ($this->getCoordinate() && !$this->getDontWantToBeContacted() && $this->getCoordinate()->getCity()) ? $this->getCoordinate()->getCity() : '',
            'zipCode' => ($this->getCoordinate() && !$this->getDontWantToBeContacted() && $this->getCoordinate()->getCity()) ? $this->getCoordinate()->getCity()->getZipCode() : '',
            'mt_name' => '',
            'rep_function' => '',
            'slug' => $this->getSlug(),
        ];
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Person
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return Person
     */
    public function setSlug($slug) {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug() {
        return $this->slug;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Person
     */
    public function setName($name) {
        $this->name = ltrim($name);

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getLastName() {
        return $this->name;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Person
     */
    public function setLastName($name) {
        $this->name = ltrim($name);

        return $this;
    }

    /**
     * Set firstName.
     *
     * @param string $firstName
     *
     * @return Person
     */
    public function setFirstName($firstName) {
        $this->firstName = ltrim($firstName);

        return $this;
    }

    /**
     * Get firstName.
     *
     * @return string
     */
    public function getFirstName() {
        return $this->firstName;
    }

    /**
     * Set coordinate.
     *
     *
     * @return Person
     */
    public function setCoordinate(\PostparcBundle\Entity\Coordinate $coordinate = null) {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * Get coordinate.
     *
     * @return \PostparcBundle\Entity\Coordinate
     */
    public function getCoordinate() {
        return $this->coordinate;
    }

    /**
     * Set civility.
     *
     *
     * @return Person
     */
    public function setCivility(\PostparcBundle\Entity\Civility $civility = null) {
        $this->civility = $civility;

        return $this;
    }

    /**
     * Get civility.
     *
     * @return \PostparcBundle\Entity\Civility
     */
    public function getCivility() {
        return $this->civility;
    }

    /**
     * Add pfo.
     *
     *
     * @return Person
     */
    public function addPfo(\PostparcBundle\Entity\Pfo $pfo) {
        $this->pfos[] = $pfo;

        return $this;
    }

    /**
     * Remove pfo.
     */
    public function removePfo(\PostparcBundle\Entity\Pfo $pfo) {
        $this->pfos->removeElement($pfo);
    }

    /**
     * Get pfos.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfos() {
        return $this->pfos;
    }

    /**
     * Add preferedEmail.
     *
     *
     * @return Person
     */
    public function addPreferedEmail(\PostparcBundle\Entity\Email $preferedEmail) {
        $this->preferedEmails[] = $preferedEmail;

        return $this;
    }

    /**
     * Check preferedEmail.
     *
     *
     * @return bool True if the person has the email as a preferred email, false otherwise
     */
    public function checkPreferedEmail(?\PostparcBundle\Entity\Email $email):bool {
        if ($email === null) {
            return false;
        }
        return $this->preferedEmails->contains($email);
    }
    /**
     * Has preferedEmail.
     *
     *
     * @return bool True if the person has an email
     */
    public function hasPreferedEmail():bool {
         return !$this->preferedEmails->isEmpty();
    }    

    /**
     * Remove preferedEmail.
     */
    public function removePreferedEmail(\PostparcBundle\Entity\Email $preferedEmail) {
        $this->preferedEmails->removeElement($preferedEmail);
    }

    /**
     * Get preferedEmails.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPreferedEmails() {
        return $this->preferedEmails;
    }

    /**
     * Add pfoPersonGroup.
     *
     *
     * @return Person
     */
    public function addPfoPersonGroup(\PostparcBundle\Entity\PfoPersonGroup $pfoPersonGroup) {
        $this->pfoPersonGroups[] = $pfoPersonGroup;

        return $this;
    }

    /**
     * Remove pfoPersonGroup.
     */
    public function removePfoPersonGroup(\PostparcBundle\Entity\PfoPersonGroup $pfoPersonGroup) {
        $this->pfoPersonGroups->removeElement($pfoPersonGroup);
    }

    /**
     * Get pfoPersonGroups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfoPersonGroups() {
        return $this->pfoPersonGroups;
    }

    /**
     * Set observation.
     *
     * @param string $observation
     *
     * @return Person
     */
    public function setObservation($observation) {
        $this->observation = $observation;

        return $this;
    }

    /**
     * Get observation.
     *
     * @return string
     */
    public function getObservation() {
        return $this->observation;
    }

    /**
     * Set profession.
     *
     * @param \PostparcBundle\Entity\Profession|null $profession
     *
     * @return Person
     */
    public function setProfession(\PostparcBundle\Entity\Profession $profession = null) {
        $this->profession = $profession;

        return $this;
    }

    /**
     * Get profession.
     *
     * @return \PostparcBundle\Entity\Profession|null
     */
    public function getProfession() {
        return $this->profession;
    }

    /**
     * Set birthLocation.
     *
     * @param \DateTime $birthLocation
     *
     * @return Person
     */
    public function setBirthLocation(City $birthLocation) {
        $this->birthLocation = $birthLocation;

        return $this;
    }

    /**
     * Get birthLocation.
     *
     * @return \DateTime
     */
    public function getBirthLocation() {
        return $this->birthLocation;
    }

    /**
     * Add representation.
     *
     *
     * @return Person
     */
    public function addRepresentation(\PostparcBundle\Entity\Representation $representation) {
        $this->representations[] = $representation;

        return $this;
    }

    /**
     * Remove representation.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeRepresentation(\PostparcBundle\Entity\Representation $representation) {
        return $this->representations->removeElement($representation);
    }

    /**
     * Get representations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRepresentations() {
        return $this->representations;
    }

    /**
     * Get events.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents() {
        $events = [];
        foreach ($this->getEventPersons() as $eventPerson) {
            $events[] = $eventPerson->getEvent();
        }

        return $events;
    }

    /**
     * Set birthDate.
     *
     * @param \DateTime|\DateTimeImmutable $birthDate
     *
     * @return Person
     */
    public function setBirthDate(\DateTimeInterface $birthDate = null) {
        $this->birthDate = $birthDate;

        return $this;
    }

    /**
     * Get birthDate.
     *
     * @return \DateTime|null
     */
    public function getBirthDate() {
        return $this->birthDate;
    }

    /**
     * Set isShared.
     *
     * @param bool $isShared
     *
     * @return Person
     */
    public function setIsShared($isShared) {
        $this->isShared = $isShared;

        return $this;
    }

    /**
     * Get isShared.
     *
     * @return bool
     */
    public function getIsShared() {
        return $this->isShared;
    }

    /**
     * Set entity.
     *
     * @param \PostparcBundle\Entity\Entity|null $entity
     *
     * @return Person
     */
    public function setEntity(\PostparcBundle\Entity\Entity $entity = null) {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity.
     *
     * @return \PostparcBundle\Entity\Entity|null
     */
    public function getEntity() {
        return $this->entity;
    }

    /**
     * Set isEditableByOtherEntities.
     *
     * @param bool $isEditableByOtherEntities
     *
     * @return Person
     */
    public function setIsEditableByOtherEntities($isEditableByOtherEntities) {
        $this->isEditableByOtherEntities = $isEditableByOtherEntities;

        return $this;
    }

    /**
     * Get isEditableByOtherEntities.
     *
     * @return bool
     */
    public function getIsEditableByOtherEntities() {
        return $this->isEditableByOtherEntities;
    }

    /**
     * Set nbMinorChildreen.
     *
     * @param int $nbMinorChildreen
     *
     * @return Person
     */
    public function setNbMinorChildreen($nbMinorChildreen) {
        $this->nbMinorChildreen = $nbMinorChildreen;

        return $this;
    }

    /**
     * Get nbMinorChildreen.
     *
     * @return int
     */
    public function getNbMinorChildreen() {
        return $this->nbMinorChildreen;
    }

    /**
     * Set nbMajorChildreen.
     *
     * @param int $nbMajorChildreen
     *
     * @return Person
     */
    public function setNbMajorChildreen($nbMajorChildreen) {
        $this->nbMajorChildreen = $nbMajorChildreen;

        return $this;
    }

    /**
     * Get nbMajorChildreen.
     *
     * @return int
     */
    public function getNbMajorChildreen() {
        return $this->nbMajorChildreen;
    }

    /**
     * Set env.
     *
     * @param string $env
     *
     * @return Person
     */
    public function setEnv($env) {
        $this->env = $env;

        return $this;
    }

    /**
     * Get env.
     *
     * @return string
     */
    public function getEnv() {
        return $this->env;
    }

    /**
     * Add tag.
     *
     *
     * @return Person
     */
    public function addTag(\PostparcBundle\Entity\Tag $tag) {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag.
     */
    public function removeTag(\PostparcBundle\Entity\Tag $tag) {
        $this->tags->removeElement($tag);
    }

    /**
     * Get tags.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * Add eventPerson.
     *
     *
     * @return Person
     */
    public function addEventPerson(\PostparcBundle\Entity\EventPersons $eventPerson) {
        $this->eventPersons[] = $eventPerson;

        return $this;
    }

    /**
     * Remove eventPerson.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventPerson(\PostparcBundle\Entity\EventPersons $eventPerson) {
        return $this->eventPersons->removeElement($eventPerson);
    }

    /**
     * Get eventPersons.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventPersons() {
        return $this->eventPersons;
    }

    /**
     * Set dontWantToBeContacted.
     *
     * @param bool $dontWantToBeContacted
     *
     * @return Person
     */
    public function setDontWantToBeContacted($dontWantToBeContacted) {
        $this->dontWantToBeContacted = $dontWantToBeContacted;

        return $this;
    }

    /**
     * Get dontWantToBeContacted.
     *
     * @return bool
     */
    public function getDontWantToBeContacted() {
        return $this->dontWantToBeContacted;
    }

    /**
     * Set dontShowCoordinateForReaders.
     *
     * @param bool $dontShowCoordinateForReaders
     *
     * @return Person
     */
    public function setDontShowCoordinateForReaders($dontShowCoordinateForReaders) {
        $this->dontShowCoordinateForReaders = $dontShowCoordinateForReaders;

        return $this;
    }

    /**
     * Get dontShowCoordinateForReaders.
     *
     * @return bool
     */
    public function getDontShowCoordinateForReaders() {
        return $this->dontShowCoordinateForReaders;
    }

    /**
     * Set image.
     *
     * @param string|null $image
     *
     * @return Person
     */
    public function setImage($image = null) {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string|null
     */
    public function getImage() {
        return $this->image;
    }

    /*
     * *******************   SPECIALS METHODS FOR UPLOAD FILE *********************
     */

    /**
     * @return string
     */
    public function getFullImagePath() {
        return null === $this->image ? null : $this->getUploadRootDir() . $this->image;
    }

    /**
     * @return string
     */
    public function getwebPath() {
        return null === $this->image ? null : 'uploads/personsImages/' . $this->env . '/' . $this->id . '/' . $this->image;
    }

    /**
     * @return string
     */
    protected function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir() . $this->getId() . '/';
    }

    /**
     * @return string
     */
    protected function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        $dir = __DIR__ . '/../../../web/uploads/personsImages/' . $this->env;
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        return $dir . '/';
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadImage() {
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
    public function moveImage() {
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
    public function removeImage() {
        if (file_exists($this->getFullImagePath())) {
            unlink($this->getFullImagePath());
            rmdir($this->getUploadRootDir());
        }
    }

    public function getEmailsArray() {
        $emails = [];
        if (count($this->getPreferedEmails()) > 0) {
            foreach ($this->getPreferedEmails() as $email) {
                $emails[] = $email->__toString();
            }
        } elseif ($this->getCoordinate() && $this->getCoordinate()->getEmail()) {
            $emails[] = $this->getCoordinate()->getEmail()->__toString();
        }

        return $emails;
    }
    
    public function getFonctions() {
        
        $pfos = $this->pfos;
        $fonctions = array_map(function ($pfo){
            if ($pfo && $pfo->getPersonFunction()){
                return $pfo->getPersonFunction()->getName();
            }
        }, $pfos->toArray());  
        
        return $fonctions;
    }
    
    public function getOrganizations() {
        $pfos = $this->pfos;
        $organizations = array_map(function ($pfo){
            if ($pfo && $pfo->getOrganization()){
                return $pfo->getOrganization()->getName();
            }
        }, $pfos->toArray());
        return $organizations;
    }


    /**
     * Add eventAlert.
     *
     * @param \PostparcBundle\Entity\EventAlert $eventAlert
     *
     * @return Person
     */
    public function addEventAlert(\PostparcBundle\Entity\EventAlert $eventAlert)
    {
        $this->eventAlerts[] = $eventAlert;

        return $this;
    }

    /**
     * Remove eventAlert.
     *
     * @param \PostparcBundle\Entity\EventAlert $eventAlert
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
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
}
