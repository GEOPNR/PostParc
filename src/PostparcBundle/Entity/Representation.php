<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JeroenDesloovere\VCard\VCard;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;
use PostparcBundle\Entity\Traits\EntitySoftDeletableTrait;
use PostparcBundle\Entity\Traits\EntityLockableTrait;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\RepresentationRepository")
 * @ORM\Table(name="representation", indexes={@ORM\Index(name="representation_slugs", columns={"slug"})})
 * @Gedmo\Loggable
 * @ORM\HasLifecycleCallbacks()
 */
class Representation {

    public $mandateDuration;

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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="representationsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="representationsUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @var elected
     *
     * @ORM\Column( type="boolean", options={"default" = "1"}, nullable=true)
     * @Gedmo\Versioned
     */
    protected $elected;

    /**
     * @ORM\ManyToOne(targetEntity="MandateType", inversedBy="representations",  cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    protected $mandateType;

    /**
     * @ORM\OneToOne(targetEntity="Coordinate", inversedBy="representation",  cascade={"remove", "persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $coordinate;

    /**
     * @ORM\Column(name="begin_date", type="datetime", nullable=true)
     */
    protected $beginDate;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    protected $endDate;

    /**
     * @ORM\Column(name="alert_date", type="datetime", nullable=true)
     */
    protected $alertDate;

    /**
     * @ORM\Column(name="mandat_duration", type="integer", nullable=true)
     */
    protected $mandatDuration;

    /**
     * @ORM\Column(name="estimated_time", type="integer", nullable=true)
     */
    protected $estimatedTime;

    /**
     * @ORM\Column(name="estimated_cost", type="integer", nullable=true)
     */
    protected $estimatedCost;

    /**
     * @ORM\Column(name="periodicity", type="integer", nullable=true)
     */
    protected $periodicity;

    /**
     * @var bool
     *
     * @ORM\Column(name="send_alert", type="boolean",  options={"default" = "0"})
     */
    protected $sendAlert;

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
     * @ORM\Column(name="nb_month_before_alert", type="integer", nullable=true)
     */
    protected $nbMonthBeforeAlert;

    /**
     * @var bool
     *
     * @ORM\Column(name="mandatDuration_is_unknown", type="boolean",  options={"default" = "0"})
     */
    protected $mandatDurationIsUnknown;

    /**
     * @var text
     *
     * @ORM\Column(name="observation", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $observation;

    /**
     * @ORM\ManyToMany(targetEntity="Attachment", cascade={"remove","persist"})
     * @ORM\JoinTable(name="representation_attachments")
     */
    protected $attachments;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="representations", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     * @Gedmo\Versioned
     */
    protected $organization;

    /**
     * @ORM\ManyToOne(targetEntity="Pfo", inversedBy="representations", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     * @Gedmo\Versioned
     */
    protected $pfo;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="representations", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     * @Gedmo\Versioned
     */
    protected $person;

    /**
     * @ORM\OneToMany(targetEntity="EventRepresentations", mappedBy="representation",
     * cascade={"persist", "remove"})
     */
    protected $eventRepresentations;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="representationAlerters")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    protected $alerter;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="representations")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;

    /**
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="representations", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @ORM\OrderBy({"name" = "ASC"})
     * @Gedmo\Versioned
     */
    protected $service;

    /**
     * @ORM\ManyToOne(targetEntity="PersonFunction", inversedBy="representations")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @ORM\OrderBy({"name" = "ASC"})
     * @Gedmo\Versioned
     */
    protected $personFunction;

    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="representations")
     * @ORM\JoinTable(name="representation_groups")
     */
    protected $groups;

    /**
     * @ORM\ManyToOne(targetEntity="NatureOfRepresentation", inversedBy="representations",  cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     * @ORM\OrderBy({"name" = "ASC"})
     */
    protected $natureOfRepresentation;

    /**
     * @ORM\ManyToOne(targetEntity="Coordinate", inversedBy="representationsPreferedCoordinateAddress", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Gedmo\Versioned
     */
    protected $preferedCoordinateAddress;

    /**
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="representationsPreferedEmail", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Gedmo\Versioned
     */
    protected $preferedEmail;

    /**
     * @ORM\ManyToMany(targetEntity="Tag" , inversedBy="representations")
     * @ORM\JoinTable(name="representation_tags")
     */
    private $tags;
    
    /**
     * @ORM\ManyToMany(targetEntity="EventAlert", mappedBy="eventAlertRepresentations")
     */
    protected $eventAlerts;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->attachments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->eventRepresentations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->sendAlert = false;
        $this->isShared = false;
        $this->mandatDurationIsUnknown = false;
    }

    /**
     * @return string
     */
    public function getClassName() {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function getApiFormated($format = 'object') {
        $formated = [
            'id' => $this->id,
            'name' => $this->name,
            'person' => $this->getPerson() !== null ? $this->getPerson()->getApiFormated('list') : null,
            'organization' => $this->getOrganization() !== null ? $this->getOrganization()->getApiFormated('list') : null,
            'personFunction' => $this->getPersonFunction() !== null ? $this->getPersonFunction()->getName() : null,
            'service' => $this->getService() !== null ? $this->getService()->getName() : null,
            'pfo' => $this->getPfo() !== null ? $this->getPfo()->getApiFormated('list') : null,
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
        foreach ($this->getGroups() as $group) {
            $groups[] = [
                'id' => $group->getId(),
                'name' => $group->getName(),
                'slug' => $group->getSlug()
            ];
        }
        $formated['groups'] = $groups;

        if ('object' == $format) {
            $formated = array_merge(
                    $formated,
                    [
                        'beginDate' => $this->beginDate,
                        'coordinate' => $this->getCoordinateObject() ? $this->getCoordinateObject()->getApiFormated() : null,
                        'endDate' => $this->endDate,
                        'estimatedCost' => $this->estimatedCost,
                        'mandateDuration' => $this->mandateDuration,
                        'natureOfRepresentation' => $this->getNatureOfRepresentation() ? $this->getNatureOfRepresentation()->getName() : null,
                        'observation' => $this->observation,
                    ]
            );
        }

        return $formated;
    }

    /**
     * function to know the sexe of the personne associate to the representation.
     *
     * @return string
     */
    public function getSexe() {
        $sexe = 'male';
        if ($this->getPerson() && $this->getPerson()->getCivility()->getIsFeminine()) {
            return 'female';
        }
        if ($this->getPfo() && $this->getPfo()->getPerson() && $this->getPfo()->getPerson()->getCivility()->getIsFeminine()) {
            return 'female';
        }

        return $sexe;
    }

    /**
     * return coordinate associate to representation.
     *
     * @return type
     */
    public function getCoordinateObject() {
        if ($this->getPreferedCoordinateAddress() && $this->getPreferedCoordinateAddress()->getCity()) {
            return $this->getPreferedCoordinateAddress();
        }
        if ($this->getCoordinate() && $this->getCoordinate()->getCity()) {
            return $this->getCoordinate();
        }
        if ($this->getPerson() && $this->getPerson()->getCoordinate() && $this->getPerson()->getCoordinate()->getCity()) {
            return $this->getPerson()->getCoordinate();
        }
        if ($this->getPfo()) {
            if ($this->getPfo()->getPreferedCoordinateAddress()) {
                return $this->getPfo()->getPreferedCoordinateAddress();
            } elseif ($this->getPfo()->getPerson()) {
                return $this->getPfo()->getPerson()->getCoordinate();
            } elseif ($this->getPfo()->getOrganization()) {
                return $this->getPfo()->getOrganization()->getCoordinate();
            }
        }
        if ($this->getOrganization() && $this->getOrganization()->getCoordinate() && $this->getOrganization()->getCoordinate()->getCity()) {
            return $this->getOrganization()->getCoordinate();
        }


        return $this->getCoordinate();
    }

    /**
     * return dom bloc address for documents.
     *
     * @return type
     */
    public function getCoordinateBlockForDocument() {
        $coord = '';
        $person = null;
        $coordinate = $this->getCoordinateObject();
        if ($this->getPerson() !== null) {
            $person = $this->getPerson();
        }
        if ($this->getPfo() !== null) {
            $person = $this->getPfo()->getPerson();
        }
        if ($person !== null) {
            $coord .= $person->getCivility() . ' ' . $person->getName() . ' ' . $person->getFirstName() . '<br/>';
        }

        if ($this->getOrganization() !== null) {
            $coord .= $this->getOrganization() . '<br/>';
        }
        if ($coordinate) {
            $coord .= $coordinate->getFormatedAddress();
        }

        return $coord;
    }

    public function getCoordinateStringForDuplicateSearch() {
        $coord = '';
        $person = null;
        $coordinate = $this->getCoordinateObject();
        if ($this->getPerson() !== null) {
            $person = $this->getPerson();
        }
        if ($this->getPfo() !== null) {
            $person = $this->getPfo()->getPerson();
        }
        if ($person !== null) {
            $coord .= $person->getCivility() . ' ' . $person->getName() . ' ' . $person->getFirstName();
        }
        if ($coordinate) {
            $coord .= $coordinate->__toString();
        }

        return $coord;
    }

    public function getScalarInfos() {
        $coordinate = false;
        if ($this->getPfo() && $this->getPfo()->getPerson() && $this->getPfo()->getPerson()->getCoordinate()) {
            $coordinate = $this->getPfo()->getCoordinate();
        }
        if ($this->getPerson() && $this->getPerson()->getCoordinate()) {
            $coordinate = $this->getPerson()->getCoordinate();
        }

        return [
            'object' => $this,
            'p_id' => $this->id,
            'p_firstName' => $this->getPfo() !== null ? $this->getPfo()->getPerson()->getFirstName() : $this->getPerson()->getFirstName(),
            'p_name' => $this->getPfo() !== null ? $this->getPfo()->getPerson()->getFirstName() : $this->getPerson()->getName(),
            'p_civility' => $this->getPfo() !== null ? $this->getPfo()->getPerson()->getFirstName() : $this->getPerson()->getCivility(),
            'coord.addressLine1' => $coordinate ? $coordinate->getAddressLine1() : '',
            'coord.addressLine2' => $coordinate ? $coordinate->getAddressLine2() : '',
            'coord.cedex' => $coordinate ? $coordinate->getCedex() : '',
            'cityName' => $coordinate ? $coordinate->getCity() : '',
            'zipCode' => $coordinate && $coordinate->getCity() ? $coordinate->getCity()->getZipCode() : '',
            'pfo_service' => $this->getPfo() && $this->getPfo()->getService() ? $this->getPfo()->getService() : '',
            'pfo_additionalFunction' => $this->getPfo() && $this->getPfo()->getAdditionalFunction() ? $this->getPfo()->getAdditionalFunction() : '',
            'o_name' => $this->getOrganization() !== null ? $this->getOrganization() : '',
            'mt_name' => $this->getMandateType() !== null ? $this->getMandateType() : '',
            'rep_function' => $this->getPersonFunction() !== null ? $this->getPersonFunction() : '',
            'slug' => $this->getSlug(),
        ];
    }

    /**
     * print sticker method.
     *
     * @param Request $postRequest
     *
     * @return string
     */
    public function getPrintForSticker($postRequest, $user, $fromPfo = 0, $personnalFieldsRestriction = []) {
        $content = '';
        $separator = '';
        $person = null;
        if ($postRequest->has('representation')) {
            $representationOptions = $postRequest->get('representation');
            // person
            if (isset($representationOptions['person']) && $postRequest->has('person')) {
                // si representation liée à pfo alors affichage nom organisme en 1ere ligne
                if ($this->getPfo() && $this->getPfo()->getOrganization()) {
                    $content .= $this->getPfo()->getOrganization() . '<br/>';
                }
                $tabFields = $postRequest->get('person');
                $person = $this->getPerson();
                if ($person === null) {
                    $person = $this->getPfo() !== null ? $this->getPfo()->getPerson() : null;
                }
                if ($person !== null) {
                    if (isset($tabFields['civility'])) {
                        $content .= $separator . $person->getCivility();
                        $separator = ' ';
                    }
                    if (isset($tabFields['lastName'])) {
                        $content .= $separator . trim($person->getName());
                        $separator = ' ';
                    }
                    if (isset($tabFields['firstName'])) {
                        $content .= $separator . trim($person->getFirstName());
                        $separator = ' ';
                    }
                    $content .= '<br/>';
                }
            }
            // service
            if (isset($representationOptions['service']) && $this->getService()) {
                $content .= trim($this->getService()) . '<br/>';
            }
            // function
            if (isset($representationOptions['function']) && $this->getPersonFunction()) {
                $separator = '';
                if (isset($representationOptions['civilityFunction']) && $person && $person->getCivility()) {
                    $content .= $separator . $person->getCivility();
                    $separator = ' ';
                }
                if (isset($representationOptions['particleFunction'])) {
                    $content .= $separator;
                    $content .= ($person && $person->getCivility() && $person->getCivility()->getIsFeminine()) ? $this->getPersonFunction()->getWomenParticle() : $this->getPersonFunction()->getMenParticle();
                    $separator = ' ';
                }
                $content .= $separator;
                $content .= ($person && $person->getCivility() && $person->getCivility()->getIsFeminine()) ? $this->getPersonFunction()->getWomenName() : $this->getPersonFunction()->getName();
                $content .= '<br/>';
            }
            // organization
            if (isset($representationOptions['organization']) && $this->getOrganization()) {
                if ($postRequest->has('organization')) {
                    $tabFieldsOrganization = $postRequest->get('organization');
                    $printName = false;
                    $separator = '';
                    if (isset($tabFieldsOrganization['name'])) {
                        // cas particulier organisme sans intitulé mais avec abreviation
                        if (!strlen(trim($this->getOrganization()->getName())) && strlen(trim($this->getOrganization()->getAbbreviation()))) {
                            $content .= $separator . trim($this->getOrganization()->getAbbreviation());
                        } else {
                            $content .= $separator . trim($this->getOrganization()->getName());
                            $printName = true;
                        }
                        $separator = ' ';
                    }
                    if (isset($tabFieldsOrganization['abbreviation'])) {
                        // cas particulier organisme avec intitulé mais sans abreviation
                        if (!strlen(trim($this->getOrganization()->getAbbreviation())) && strlen(trim($this->getOrganization()->getName()))) {
                            if (!$printName) {
                                $content .= $separator . trim($this->getOrganization()->getName());
                            }
                        } else {
                            $content .= $separator . trim($this->getOrganization()->getAbbreviation());
                        }
                    }
                } else {
                    $content .= trim($this->getOrganization()->getName());
                }
            }
            if (isset($representationOptions['coordinate'])) {
                $coordinate = $this->getCoordinateObject();
                if ($coordinate) {
                    $content .= $coordinate->getPrintForSticker($postRequest, $user, $fromPfo, $personnalFieldsRestriction);
                }
            }

            return nl2br($content);
        }

        return '';
    }

    public function generateVcardContent() {
        $vcard = new VCard();

        if ($this->getPerson() !== null) {
            $person = $this->getPerson();
            $pfo = null;
        } else {
            $pfo = $this->getPfo();
            $person = $pfo !== null ? $pfo->getPerson() : null;
        }

        $additional = '';
        $prefix = '';
        $suffix = '';
        $lastname = '';
        $firstname = '';

        if ($person !== null) {
            $lastname = $person->getLastName();
            $firstname = $person->getFirstName();
            if ($person->getBirthDate() !== null) {
                $vcard->addBirthday($person->getBirthDate()->format('Y-m-d'));
            }
        }
        // add personal data
        $vcard->addName($lastname, $firstname, $additional, $prefix, $suffix);

        // add work data
        $vcard->addCompany($this->getOrganization());
        $vcard->addJobtitle($this->getMandateType());
        $vcard->addRole($this->getPersonFunction());
        $coordinate = $this->getPreferedCoordinateAddress() ? $this->getPreferedCoordinateAddress() : $this->getCoordinate();
        if ($coordinate) {
            $vcard->addAddress(null, null, $coordinate->getAddressLine1() . ' ' . $coordinate->getAddressLine2(), $coordinate->getCity(), null, $coordinate->getCity() ? $coordinate->getCity()->getZipCode() : '', $coordinate->getCity() ? $coordinate->getCity()->getCountry() : '', 'WORK');
            $vcard->addPhoneNumber($coordinate->getPhone(), 'PREF;WORK');
            $vcard->addPhoneNumber($coordinate->getMobilePhone(), 'CELL');
            $vcard->addPhoneNumber($coordinate->getFax(), 'FAX');
            $vcard->addURL($coordinate->getWebSite(), 'WORK');
        }
        if ($this->getPreferedEmail()) {
            $vcard->addEmail($this->getPreferedEmail(), 'WORK');
        } elseif ($coordinate) {
            $vcard->addEmail($coordinate->getEmail(), 'WORK');
        }

        return $vcard->getOutput();
    }

    /**
     * @return type
     */
    public function __toString() {
        return strlen($this->name) !== 0 ? $this->name : ' ';
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
     * Set elected.
     *
     * @param bool $elected
     *
     * @return Representation
     */
    public function setElected($elected) {
        $this->elected = $elected;

        return $this;
    }

    /**
     * Get elected.
     *
     * @return bool
     */
    public function getElected() {
        return $this->elected;
    }

    /**
     * Set beginDate.
     *
     * @param \DateTime $beginDate
     *
     * @return Representation
     */
    public function setBeginDate(?\DateTimeInterface $beginDate) {
        $this->beginDate = $beginDate;

        return $this;
    }

    /**
     * Get beginDate.
     *
     * @return \DateTime
     */
    public function getBeginDate() {
        return $this->beginDate;
    }

    /**
     * Set mandatDuration.
     *
     * @param int $mandatDuration
     *
     * @return Representation
     */
    public function setMandatDuration($mandatDuration) {
        $this->mandatDuration = $mandatDuration;

        return $this;
    }

    /**
     * Get mandatDuration.
     *
     * @return int
     */
    public function getMandatDuration() {
        return $this->mandatDuration;
    }

    /**
     * Set estimatedTime.
     *
     * @param int $estimatedTime
     *
     * @return Representation
     */
    public function setEstimatedTime($estimatedTime) {
        $this->estimatedTime = $estimatedTime;

        return $this;
    }

    /**
     * Get estimatedTime.
     *
     * @return int
     */
    public function getEstimatedTime() {
        return $this->estimatedTime;
    }

    /**
     * Set estimatedCost.
     *
     * @param int $estimatedCost
     *
     * @return Representation
     */
    public function setEstimatedCost($estimatedCost) {
        $this->estimatedCost = $estimatedCost;

        return $this;
    }

    /**
     * Get estimatedCost.
     *
     * @return int
     */
    public function getEstimatedCost() {
        return $this->estimatedCost;
    }

    /**
     * Set periodicity.
     *
     * @param int $periodicity
     *
     * @return Representation
     */
    public function setPeriodicity($periodicity) {
        $this->periodicity = $periodicity;

        return $this;
    }

    /**
     * Get periodicity.
     *
     * @return int
     */
    public function getPeriodicity() {
        return $this->periodicity;
    }

    /**
     * Set mandateType.
     *
     * @param \PostparcBundle\Entity\MandateType|null $mandateType
     *
     * @return Representation
     */
    public function setMandateType(\PostparcBundle\Entity\MandateType $mandateType = null) {
        $this->mandateType = $mandateType;

        return $this;
    }

    /**
     * Get mandateType.
     *
     * @return \PostparcBundle\Entity\MandateType|null
     */
    public function getMandateType() {
        return $this->mandateType;
    }

    /**
     * Set coordinate.
     *
     * @param \PostparcBundle\Entity\Coordinate|null $coordinate
     *
     * @return Representation
     */
    public function setCoordinate(\PostparcBundle\Entity\Coordinate $coordinate = null) {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * Get coordinate.
     *
     * @return \PostparcBundle\Entity\Coordinate|null
     */
    public function getCoordinate() {
        return $this->coordinate;
    }

    /**
     * Add attachment.
     *
     *
     * @return Representation
     */
    public function addAttachment(\PostparcBundle\Entity\Attachment $attachment) {
        $this->attachments[] = $attachment;

        return $this;
    }

    /**
     * Remove attachment.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeAttachment(\PostparcBundle\Entity\Attachment $attachment) {
        return $this->attachments->removeElement($attachment);
    }

    /**
     * Get attachments.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttachments() {
        return $this->attachments;
    }

    /**
     * Set organization.
     *
     * @param \PostparcBundle\Entity\Organization|null $organization
     *
     * @return Representation
     */
    public function setOrganization(\PostparcBundle\Entity\Organization $organization = null) {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization.
     *
     * @return \PostparcBundle\Entity\Organization|null
     */
    public function getOrganization() {
        return $this->organization;
    }

    /**
     * Set pfo.
     *
     * @param \PostparcBundle\Entity\Pfo|null $pfo
     *
     * @return Representation
     */
    public function setPfo(\PostparcBundle\Entity\Pfo $pfo = null) {
        $this->pfo = $pfo;

        return $this;
    }

    /**
     * Get pfo.
     *
     * @return \PostparcBundle\Entity\Pfo|null
     */
    public function getPfo() {
        return $this->pfo;
    }

    /**
     * Set person.
     *
     * @param \PostparcBundle\Entity\Person|null $person
     *
     * @return Representation
     */
    public function setPerson(\PostparcBundle\Entity\Person $person = null) {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person.
     *
     * @return \PostparcBundle\Entity\Person|null
     */
    public function getPerson() {
        return $this->person;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Representation
     */
    public function setName($name) {
        $this->name = $name;

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
     * Set slug.
     *
     * @param string $slug
     *
     * @return Representation
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
     * @ORM\PrePersist
     */
    public function prePersist() {
        $this->updateName();

        $this->updateEnDateValue();
        if ($this->getSendAlert()) {
            $this->updateAlertDate();
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate() {
        $this->updateName();

        $this->updateEnDateValue();
        if ($this->getSendAlert()) {
            $this->updateAlertDate();
        }
    }

    public function updateName() {
        $name = '';
        $separator = '';
        if ($this->person) {
            $name .= $this->getPerson()->getLastName() . ' ' . $this->getPerson()->getFirstName();
            $separator .= ' / ';
        }
        if ($this->pfo) {
            if ($this->getPfo()->getPerson()) {
                $name .= $this->getPfo()->getPerson()->getLastName() . ' ' . $this->getPfo()->getPerson()->getFirstName();
            }
            if ($this->getPfo()->getOrganization()) {
                $name .= ' (';
                if ($this->getPfo()->getOrganization()->getAbbreviation() !== '' && $this->getPfo()->getOrganization()->getAbbreviation() !== '0') {
                    $name .= $this->getPfo()->getOrganization()->getAbbreviation();
                } else {
                    $name .= $this->getPfo()->getOrganization();
                }
                $name .= ')';
            }
            $separator .= ' / ';
        }
        if ($this->getOrganization() !== null) {
            if ($this->getOrganization()->getAbbreviation() !== '' && $this->getOrganization()->getAbbreviation() !== '0') {
                $name .= $separator . $this->getOrganization()->getAbbreviation();
            } else {
                $name .= $separator . $this->getOrganization()->getName();
            }
            $separator .= ' / ';
        }
        if ($this->getService() !== null) {
            $name .= ' (' . $this->getService() . ')';
        }
        if ($this->name !== $name) {
            $this->name = $name;
        }

        return $this->name;
    }

    /**
     * Set endDate.
     *
     * @param \DateTime $endDate
     *
     * @return Representation
     */
    public function setEndDate(\DateTimeInterface $endDate) {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate.
     *
     * @return \DateTime
     */
    public function getEndDate() {
        return $this->endDate;
    }

    /**
     * Set nbMonthBeforeAlert.
     *
     * @param int $nbMonthBeforeAlert
     *
     * @return Representation
     */
    public function setNbMonthBeforeAlert($nbMonthBeforeAlert) {
        $this->nbMonthBeforeAlert = $nbMonthBeforeAlert;

        return $this;
    }

    /**
     * Get nbMonthBeforeAlert.
     *
     * @return int
     */
    public function getNbMonthBeforeAlert() {
        return $this->nbMonthBeforeAlert;
    }

    /**
     * Set alerter.
     *
     * @param \PostparcBundle\Entity\User|null $alerter
     *
     * @return Representation
     */
    public function setAlerter(\PostparcBundle\Entity\User $alerter = null) {
        $this->alerter = $alerter;

        return $this;
    }

    /**
     * Get alerter.
     *
     * @return \PostparcBundle\Entity\User|null
     */
    public function getAlerter() {
        return $this->alerter;
    }

    /**
     * Set alertDate.
     *
     * @param \DateTime $alertDate
     *
     * @return Representation
     */
    public function setAlertDate(\DateTimeInterface $alertDate) {
        $this->alertDate = $alertDate;

        return $this;
    }

    /**
     * Get alertDate.
     *
     * @return \DateTime
     */
    public function getAlertDate() {
        return $this->alertDate;
    }

    /**
     * Set sendAlert.
     *
     * @param bool $sendAlert
     *
     * @return Representation
     */
    public function setSendAlert($sendAlert) {
        $this->sendAlert = $sendAlert;

        return $this;
    }

    /**
     * Get sendAlert.
     *
     * @return bool
     */
    public function getSendAlert() {
        return $this->sendAlert;
    }

    /**
     * Set isShared.
     *
     * @param bool $isShared
     *
     * @return Representation
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
     * @return Representation
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

    private function updateEnDateValue() {
        if ($this->beginDate && $this->mandatDuration) {
            $endingDate = clone $this->beginDate;
            $intervalString = 'P' . $this->mandatDuration . 'M';
            $endingDate->add(new \DateInterval($intervalString));

            $this->endDate = $endingDate;
        }
    }

    private function updateAlertDate() {
        if ($this->endDate) {
            $alertDate = clone $this->endDate;
            $intervalString = 'P' . $this->nbMonthBeforeAlert . 'M';
            $alertDate->sub(new \DateInterval($intervalString));

            $this->alertDate = $alertDate;
        }
    }

    /**
     * Set mandatDurationIsUnknown.
     *
     * @param bool $mandatDurationIsUnknown
     *
     * @return Representation
     */
    public function setMandatDurationIsUnknown($mandatDurationIsUnknown) {
        $this->mandatDurationIsUnknown = $mandatDurationIsUnknown;

        return $this;
    }

    /**
     * Get mandatDurationIsUnknown.
     *
     * @return bool
     */
    public function getMandatDurationIsUnknown() {
        return $this->mandatDurationIsUnknown;
    }

    /**
     * Set observation.
     *
     * @param string|null $observation
     *
     * @return Representation
     */
    public function setObservation($observation = null) {
        $this->observation = $observation;

        return $this;
    }

    /**
     * Get observation.
     *
     * @return string|null
     */
    public function getObservation() {
        return $this->observation;
    }

    /**
     * Set service.
     *
     * @param \PostparcBundle\Entity\Service|null $service
     *
     * @return Representation
     */
    public function setService(\PostparcBundle\Entity\Service $service = null) {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service.
     *
     * @return \PostparcBundle\Entity\Service|null
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Set isEditableByOtherEntities.
     *
     * @param bool $isEditableByOtherEntities
     *
     * @return Representation
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
     * Set personFunction.
     *
     * @param \PostparcBundle\Entity\PersonFunction|null $personFunction
     *
     * @return Representation
     */
    public function setPersonFunction(\PostparcBundle\Entity\PersonFunction $personFunction = null) {
        $this->personFunction = $personFunction;

        return $this;
    }

    /**
     * Get personFunction.
     *
     * @return \PostparcBundle\Entity\PersonFunction|null
     */
    public function getPersonFunction() {
        return $this->personFunction;
    }

    /**
     * Add group.
     *
     *
     * @return Representation
     */
    public function addGroup(\PostparcBundle\Entity\Group $group) {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Remove group.
     */
    public function removeGroup(\PostparcBundle\Entity\Group $group) {
        $this->groups->removeElement($group);
    }

    /**
     * Get groups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups() {
        return $this->groups;
    }

    /**
     * Set natureOfRepresentation.
     *
     *
     * @return Representation
     */
    public function setNatureOfRepresentation(\PostparcBundle\Entity\NatureOfRepresentation $natureOfRepresentation = null) {
        $this->natureOfRepresentation = $natureOfRepresentation;

        return $this;
    }

    /**
     * Get natureOfRepresentation.
     *
     * @return \PostparcBundle\Entity\NatureOfRepresentation
     */
    public function getNatureOfRepresentation() {
        return $this->natureOfRepresentation;
    }

    /**
     * Set preferedCoordinateAddress.
     *
     *
     * @return Representation
     */
    public function setPreferedCoordinateAddress(\PostparcBundle\Entity\Coordinate $preferedCoordinateAddress = null) {
        $this->preferedCoordinateAddress = $preferedCoordinateAddress;

        return $this;
    }

    /**
     * Get preferedCoordinateAddress.
     *
     * @return \PostparcBundle\Entity\Coordinate
     */
    public function getPreferedCoordinateAddress() {
        return $this->preferedCoordinateAddress;
    }

    /**
     * Set preferedEmail.
     *
     *
     * @return Representation
     */
    public function setPreferedEmail(\PostparcBundle\Entity\Email $preferedEmail = null) {
        $this->preferedEmail = $preferedEmail;

        return $this;
    }

    /**
     * Get preferedEmail.
     *
     * @return \PostparcBundle\Entity\Email
     */
    public function getPreferedEmail() {
        return $this->preferedEmail;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return Representation
     */
    public function setCreated(\DateTimeInterface $created) {
        $this->created = $created;

        return $this;
    }

    /**
     * Set updated.
     *
     * @param \DateTime $updated
     *
     * @return Representation
     */
    public function setUpdated(\DateTimeInterface $updated) {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Add eventRepresentation.
     *
     *
     * @return Representation
     */
    public function addEventRepresentation(\PostparcBundle\Entity\EventRepresentations $eventRepresentation) {
        $this->eventRepresentations[] = $eventRepresentation;

        return $this;
    }

    /**
     * Remove eventRepresentation.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventRepresentation(\PostparcBundle\Entity\EventRepresentations $eventRepresentation) {
        return $this->eventRepresentations->removeElement($eventRepresentation);
    }

    /**
     * Get eventRepresentations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventRepresentations() {
        return $this->eventRepresentations;
    }

    /**
     * Add tag.
     *
     *
     * @return Representation
     */
    public function addTag(\PostparcBundle\Entity\Tag $tag) {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag.
     *
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeTag(\PostparcBundle\Entity\Tag $tag) {
        return $this->tags->removeElement($tag);
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
     * Add eventAlert.
     *
     * @param \PostparcBundle\Entity\EventAlert $eventAlert
     *
     * @return Representation
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
