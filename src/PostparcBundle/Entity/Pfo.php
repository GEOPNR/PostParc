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
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\PfoRepository")
 * @ORM\Table(name="pfo")
 * @Gedmo\Loggable
 */
class Pfo {

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
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="pfosCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="pfosUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="pfos", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     * @Gedmo\Versioned
     */
    protected $person;

    /**
     * @ORM\ManyToOne(targetEntity="PersonFunction", inversedBy="pfos")
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Gedmo\Versioned
     */
    protected $personFunction;

    /**
     * @var bool
     *
     * @ORM\Column(name="isMainFunction", type="boolean",  options={"default" = "1"})
     */
    private $isMainFunction;

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
     * @ORM\ManyToOne(targetEntity="AdditionalFunction", inversedBy="pfos", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Gedmo\Versioned
     */
    protected $additionalFunction;

    /**
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="pfos", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Gedmo\Versioned
     */
    protected $service;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="pfos", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     * @Gedmo\Versioned
     */
    protected $organization;

    /**
     * @ORM\ManyToOne(targetEntity="City", inversedBy="pfos", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     * @Gedmo\Versioned
     */
    protected $connectingCity;

    /**
     * @ORM\ManyToOne(targetEntity="Coordinate", inversedBy="pfosPreferedCoordinateAddress", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * @Gedmo\Versioned
     */
    protected $preferedCoordinateAddress;

    /**
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="pfoEmails", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     * @Gedmo\Versioned
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=32, nullable=true)
     * @Gedmo\Versioned
     */
    private $phone;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile_phone", type="string", length=32, nullable=true)
     * @Gedmo\Versioned
     */
    private $mobilePhone;

    /**
     * @var string
     *
     * @ORM\Column(name="fax", type="string", length=32, nullable=true)
     * @Gedmo\Versioned
     */
    private $fax;

    /**
     * @var string
     *
     * @ORM\Column(name="assistant_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $assistantName;

    /**
     * @var string
     *
     * @ORM\Column(name="assistant_phone", type="string", length=32, nullable=true)
     * @Gedmo\Versioned
     */
    private $assistantPhone;    
    
    
    /**
     * @var text
     *
     * @ORM\Column(name="observation", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $observation;

    /**
     * @ORM\Column(name="hiringDate", type="datetime", nullable=true)
     */
    private $hiringDate;

    /**
     * @var int
     * @ORM\ManyToMany(targetEntity="Email", inversedBy="pfosPreferedEmails")
     * @ORM\JoinTable(name="pfo_prefered_emails")
     */
    private $preferedEmails;

    /**
     * @ORM\OneToMany(targetEntity="PfoPersonGroup", mappedBy="pfo", cascade={"remove", "persist"})
     */
    protected $pfoPersonGroups;

    /**
     * @ORM\ManyToMany(targetEntity="Tag" , inversedBy="pfos")
     * @ORM\JoinTable(name="pfo_tags")
     */
    private $tags;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="pfo", cascade={"remove", "persist"})
     */
    protected $representations;

    /**
     * @ORM\OneToMany(targetEntity="EventPfos", mappedBy="pfo",
     * cascade={"persist", "remove"})
     */
    protected $eventPfos;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="pfos")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;
    
    /**
     * @ORM\ManyToMany(targetEntity="EventAlert", mappedBy="eventAlertPfos")
     */
    protected $eventAlerts;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->preferedEmails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pfoPersonGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->representations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->eventPfos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->isMainFunction = true;
    }

    /**
     * @return string
     */
    public function __toString() {
        $return = '';
        $separator = '';
        if ($this->getPerson() && '' != $this->getPerson()) {
            $return .= $separator . $this->getPerson();
            $separator = ' / ';
        }
        if ($this->getOrganization() && '' != $this->getOrganization()) {
            if ($this->getOrganization()->getAbbreviation() && '' != $this->getOrganization()->getAbbreviation()) {
                $return .= $separator . $this->getOrganization()->getAbbreviation();
            } else {
                $return .= $separator . $this->getOrganization();
            }
            $separator = ' / ';
        }
        if ($this->getService() && '' != $this->getService()) {
            $return .= $separator . $this->getService();
            $separator = ' / ';
        }
        if ($this->getPersonFunction() && '' != $this->getPersonFunction()) {
            $functionString = $this->getPersonFunction()->__toString();
        
            if ($this->getPerson() && 'female' == $this->getPerson()->getSexe() && $this->getPersonFunction()->getWomenParticle()) {
                $functionString = $this->getPersonFunction()->getWomenName();
            }
            $return .= $separator . $functionString;
            $separator = ' / ';
            
        }

        return $return;
    }

    public function getCoordinateStringForDuplicateSearch() {
        
        $isCoordPerso = false;
        $coordinate = $this->getCoordinate();
        if ($this->getPreferedCoordinateAddress()) {
                $coordinate = $this->getPreferedCoordinateAddress();
                $persons = $coordinate->getPersons();
                if (count($persons) > 0) {
                    $isCoordPerso = true;
                }
            }
        $coord = $this->__toString();
        if($isCoordPerso) {
            $coord = $this->getPerson()->__toString();
        }
        if ($coordinate) {
            $coord .= $coordinate->__toString();
        }

        return $coord;
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
            'person' => $this->getPerson() ? $this->getPerson()->getApiFormated('list') : null,
            'organization' => $this->getOrganization() ? $this->getOrganization()->getApiFormated('list') : null,
            'personFunction' => $this->getPersonFunction() ? $this->getPersonFunction()->getName() : null,
            'service' => $this->getService() ? $this->getService()->getName() : null,
            'additionalFunction' => $this->getAdditionalFunction() ? $this->getAdditionalFunction()->getName() : null,
            'emails' => $this->getEmailsArray(),
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
            if ($group) {
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
                        'email' => $this->getEmail() ? $this->getEmail()->getEmail() : null,
                        'phone' => $this->phone,
                        'mobilephone' => $this->mobilePhone,
                        'fax' => $this->fax,
                        'observation' => $this->observation,
                        'city' => $this->getConnectingCity() ? $this->getConnectingCity()->getName() : null,
                        'coordinate' => $this->getCoordinate() ? $this->getCoordinate()->getApiFormated() : null,
                        'hiringDate' => $this->hiringDate,
                    ]
            );
        }

        return $formated;
    }

    public function getScalarInfos() {
        return [
            'object' => $this,
            'p_id' => $this->id,
            'p_firstName' => $this->getPerson() ? $this->getPerson()->getFirstName() : '',
            'p_name' => $this->getPerson() ? $this->getPerson()->getName() : '',
            'p_civility' => $this->getPerson() ? $this->getPerson()->getCivility() : '',
            'coord.addressLine1' => $this->getOrganization() && $this->getOrganization()->getCoordinate() ? $this->getOrganization()->getCoordinate()->getAddressLine1() : '',
            'coord.addressLine2' => $this->getOrganization() && $this->getOrganization()->getCoordinate() ? $this->getOrganization()->getCoordinate()->getAddressLine2() : '',
            'coord.cedex' => $this->getOrganization() && $this->getOrganization()->getCoordinate() ? $this->getOrganization()->getCoordinate()->getCedex() : '',
            'cityName' => $this->getOrganization() && $this->getOrganization()->getCoordinate() ? $this->getOrganization()->getCoordinate()->getCity() : '',
            'zipCode' => $this->getOrganization() && $this->getOrganization()->getCoordinate() && $this->getOrganization()->getCoordinate()->getCity() ? $this->getOrganization()->getCoordinate()->getCity()->getZipCode() : '',
            'pfo_service' => $this->getService() ? $this->getService() : '',
            'pfo_additionalFunction' => $this->getAdditionalFunction() ? $this->getAdditionalFunction() : '',
            'o_name' => $this->getOrganization() ? $this->getOrganization() : '',
            'mt_name' => '',
            'rep_function' => '',
            'slug' => '',
        ];
    }

    /**
     * @param Request $postRequest
     *
     * @return string
     */
    public function getPrintForSticker($postRequest, $user, $fromPfo = 0, $personnalFieldsRestriction = []) {
        $content = '';
        $separator = '';

        if ($postRequest->has('pfo')) {
            // cas particulier pour le bloc coordinate dune coordonnée liées à une personne, donc adresse perso
            $isCoordPerso = false;
            $isCoordOtherOrganization = false;
            if ($this->getPreferedCoordinateAddress()) {
                $coordinate = $this->getPreferedCoordinateAddress();
                $persons = $coordinate->getPersons();
                if (count($persons) > 0) {
                    $isCoordPerso = true;
                }
                if ($coordinate->getOrganization() && ($coordinate->getOrganization()->getId() !== $this->getOrganization()->getId())) {
                    $isCoordOtherOrganization = true;
                }
            }
            $tabFields = $postRequest->get('pfo');

            // ORGANISATION
            if (isset($tabFields['organization']) && $this->getOrganization() && !$isCoordPerso) {
                // gestion du cas particulier d'une adresse préférée
                if ($isCoordOtherOrganization) {
                    $content .= $coordinate->getOrganization()->getPrintForSticker($postRequest, $user, 1, $personnalFieldsRestriction);
                } else {
                    $content .= $this->getOrganization()->getPrintForSticker($postRequest, $user, 1, $personnalFieldsRestriction);
                }
                $separator = '<br/>';
            }
            // PERSON
            // test supplementaire pour ne pas prendre en compte les personnes avec un nom et prenom vide
            if (isset($tabFields['person']) && $this->getPerson() && (strlen($this->getPerson()->getName()) && strlen($this->getPerson()->getFirstName()))) {
                $content .= $separator . $this->getPerson()->getPrintForSticker($postRequest, $user, 1, []);
                $separator = '<br/>';
            }
            if (!$isCoordPerso && !$isCoordOtherOrganization) {
                // PARTICULE FONCTION
                $particleFunction = '';
                if (isset($tabFields['particleFunction']) && trim($this->getPersonFunction()) && !$this->getPersonFunction()->getNotPrintOnCoordinate() && $this->getPerson()) {
                    if ('female' == $this->getPerson()->getSexe() && $this->getPersonFunction()->getWomenParticle()) {
                        $particleFunction = 'Madame ' . $this->getPersonFunction()->getWomenParticle() . ' ';
                    }
                    if ('female' !== $this->getPerson()->getSexe() && $this->getPersonFunction()->getMenParticle()) {
                        $particleFunction = 'Monsieur ' . $this->getPersonFunction()->getMenParticle() . ' ';
                    }
                }
                // FONCTION
                if (isset($tabFields['function']) && trim($this->getPersonFunction()) && !$this->getPersonFunction()->getNotPrintOnCoordinate()) {
                    $function = $particleFunction . $this->getPersonFunction()->getName();
                    if ($this->getPerson() && 'female' == $this->getPerson()->getSexe() && $this->getPersonFunction()->getWomenName()) {
                        $function = $particleFunction . $this->getPersonFunction()->getWomenName();
                    }
                    $content .= $separator . trim($function);
                    $separator = '<br/>';
                }
                // ADDITIONAL FUNCTION
                if (isset($tabFields['additionalFunction']) && trim($this->getAdditionalFunction())) {
                    $additionalFunction = $this->getAdditionalFunction()->getName();
                    if ($this->getPerson() && 'female' == $this->getPerson()->getSexe() && $this->getAdditionalFunction()->getWomenName()) {
                        $additionalFunction = $this->getAdditionalFunction()->getWomenName();
                    }
                    $content .= ' ' . trim($additionalFunction);
                    $separator = '<br/>';
                }
                // SERVICE
                if (isset($tabFields['service']) && trim($this->getService())) {
                    $content .= $separator . trim($this->getService());
                    $separator = '<br/>';
                }
            }
            // COORDINATE
            if (isset($tabFields['coordinate'])) {
                if ($this->getPreferedCoordinateAddress()) {
                    $coordinate = $this->getPreferedCoordinateAddress();
                    $content .= $coordinate->getPrintForSticker($postRequest, $user, $fromPfo, $personnalFieldsRestriction);
                } elseif ($this->getOrganization() && $this->getOrganization()->getCoordinate()) {
                    $coordinate = $this->getOrganization()->getCoordinate();
                    $content .= $coordinate->getPrintForSticker($postRequest, $user, $fromPfo, $personnalFieldsRestriction);
                }
            }
        }

        return nl2br($content);
    }

    public function generateVcardContent($personnalFieldsRestriction = []) {
        $vcard = new VCard();

        if ($this->getPerson()) {
            $person = $this->getPerson();
            $lastname = $person->getLastName();
            $firstname = $person->getFirstName();
            $additional = '';
            $prefix = '';
            $suffix = '';
            // add personal data
            $vcard->addName($lastname, $firstname, $additional, $prefix, $suffix);
            if ($person->getBirthDate() && !in_array('birthDate', $personnalFieldsRestriction)) {
                $vcard->addBirthday($person->getBirthDate()->format('Y-m-d'));
            }
            if($this->getPerson()->getImage()){
                $url = __DIR__ . '/../../../web/'.$this->getPerson()->getwebPath();
                $vcard->addPhoto($url, true);
            }
        }
        // add work data
        $vcard->addCompany($this->getOrganization());
        $vcard->addJobtitle($this->getPersonFunction() . ' ' . $this->getAdditionalFunction());
        $vcard->addRole($this->getPersonFunction() . ' ' . $this->getAdditionalFunction());
        $coordinate = $this->getOrganization() ? $this->getOrganization()->getCoordinate() : null;
        if ($this->getPreferedCoordinateAddress()) {
            $coordinate = $this->getPreferedCoordinateAddress();
        }
        if ($coordinate) {
            $vcard->addAddress(null, null, $coordinate->getAddressLine1() . ' ' . $coordinate->getAddressLine2(), $coordinate->getCity(), null, $coordinate->getCity() ? $coordinate->getCity()->getZipCode() : '', $coordinate->getCity() ? $coordinate->getCity()->getCountry() : '', 'WORK');
        }

        if ( count($this->getPreferedEmails())>0 ) {
            foreach ($this->getPreferedEmails() as $email) {
                $vcard->addEmail($email, 'WORK');
            }
        } else {
            $vcard->addEmail($this->getEmail(), 'WORK');
        }
        $vcard->addPhoneNumber($this->getPhone(), 'PREF;WORK');
        $vcard->addPhoneNumber($this->getMobilePhone(), 'CELL');
        $vcard->addPhoneNumber($this->getFax(), 'FAX');
        if ($this->getOrganization() && $this->getOrganization()->getCoordinate()) {
            $vcard->addURL($this->getOrganization()->getCoordinate()->getWebSite(), 'WORK');
        }
        
        if($this->getOrganization() && $this->getOrganization()->getImage() && !($this->getPerson() && $this->getPerson()->getImage())){
            $url = __DIR__ . '/../../../web/'.$this->getOrganization()->getwebPath();
            $vcard->addPhoto($url, true);
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

        return $vcard->getOutput();
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
     * Set phone.
     *
     * @param string $phone
     *
     * @return Pfo
     */
    public function setPhone($phone) {
        $this->phone = preg_replace('/\D+/', '', $phone);

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone() {
        return wordwrap(preg_replace('/\D+/', '', $this->phone), 2, ' ', true);
    }

    /**
     * Set mobilePhone.
     *
     * @param string $mobilePhone
     *
     * @return Pfo
     */
    public function setMobilePhone($mobilePhone) {
        $this->mobilePhone = preg_replace('/\D+/', '', $mobilePhone);

        return $this;
    }

    /**
     * Get mobilePhone.
     *
     * @return string
     */
    public function getMobilePhone() {
        return wordwrap(preg_replace('/\D+/', '', $this->mobilePhone), 2, ' ', true);
    }

    /**
     * Set fax.
     *
     * @param string $fax
     *
     * @return Pfo
     */
    public function setFax($fax) {
        $this->$fax = preg_replace('/\D+/', '', $fax);

        return $this;
    }
    
    /**
     * Get fax.
     *
     * @return string
     */
    public function getFax() {
        return wordwrap(preg_replace('/\D+/', '', $this->fax), 2, ' ', true);
    }

    /**
     * Set observation.
     *
     * @param string $observation
     *
     * @return Pfo
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
     * Set person.
     *
     *
     * @return Pfo
     */
    public function setPerson(\PostparcBundle\Entity\Person $person = null) {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person.
     *
     * @return \PostparcBundle\Entity\Person
     */
    public function getPerson() {
        return $this->person;
    }

    /**
     * Set personFunction.
     *
     *
     * @return Pfo
     */
    public function setPersonFunction(\PostparcBundle\Entity\PersonFunction $personFunction = null) {
        $this->personFunction = $personFunction;

        return $this;
    }

    /**
     * Get personFunction.
     *
     * @return \PostparcBundle\Entity\PersonFunction
     */
    public function getPersonFunction() {
        return $this->personFunction;
    }

    /**
     * Set additionalFunction.
     *
     *
     * @return Pfo
     */
    public function setAdditionalFunction(\PostparcBundle\Entity\AdditionalFunction $additionalFunction = null) {
        $this->additionalFunction = $additionalFunction;

        return $this;
    }

    /**
     * Get additionalFunction.
     *
     * @return \PostparcBundle\Entity\AdditionalFunction
     */
    public function getAdditionalFunction() {
        return $this->additionalFunction;
    }

    /**
     * Set service.
     *
     *
     * @return Pfo
     */
    public function setService(\PostparcBundle\Entity\Service $service = null) {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service.
     *
     * @return \PostparcBundle\Entity\Service
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Set organization.
     *
     *
     * @return Pfo
     */
    public function setOrganization(\PostparcBundle\Entity\Organization $organization = null) {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization.
     *
     * @return \PostparcBundle\Entity\Organization
     */
    public function getOrganization() {
        return $this->organization;
    }

    /**
     * Set connectingCity.
     *
     *
     * @return Pfo
     */
    public function setConnectingCity(\PostparcBundle\Entity\City $connectingCity = null) {
        $this->connectingCity = $connectingCity;

        return $this;
    }

    /**
     * Get connectingCity.
     *
     * @return \PostparcBundle\Entity\City
     */
    public function getConnectingCity() {
        return $this->connectingCity;
    }

    /**
     * Set preferedCoordinateAddress.
     *
     *
     * @return Pfo
     */
    public function setPreferedCoordinateAddress(\PostparcBundle\Entity\Coordinate $preferedCoordinateAddress = null) {
        $this->preferedCoordinateAddress = $preferedCoordinateAddress;

        return $this;
    }

    public function getCoordinate() {
        if ($this->getPreferedCoordinateAddress()) {
            return $this->getPreferedCoordinateAddress();
        }
        if ($this->getOrganization() && $this->getOrganization()->getCoordinate()) {
            return $this->getOrganization()->getCoordinate();
        }
        if ($this->getPerson() && $this->getPerson()->getCoordinate()) {
            return $this->getPerson()->getCoordinate();
        }
        return null;
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
     * Set email.
     *
     *
     * @return Pfo
     */
    public function setEmail(\PostparcBundle\Entity\Email $email = null) {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return \PostparcBundle\Entity\Email
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * Add preferedEmail.
     *
     *
     * @return Pfo
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
     * @return Pfo
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
     * Set isMainFunction.
     *
     * @param bool $isMainFunction
     *
     * @return Pfo
     */
    public function setIsMainFunction($isMainFunction) {
        $this->isMainFunction = $isMainFunction;

        return $this;
    }

    /**
     * Get isMainFunction.
     *
     * @return bool
     */
    public function getIsMainFunction() {
        return $this->isMainFunction;
    }

    /**
     * Add tag.
     *
     *
     * @return Pfo
     */
    public function addTag(\PostparcBundle\Entity\Tag $tag) {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
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
     * Set hiringDate.
     *
     * @param \DateTime|\DateTimeImmutable $hiringDate
     *
     * @return Pfo
     */
    public function setHiringDate(\DateTimeInterface $hiringDate = null) {
        $this->hiringDate = $hiringDate;

        return $this;
    }

    /**
     * Get hiringDate.
     *
     * @return \DateTime|null
     */
    public function getHiringDate() {
        return $this->hiringDate;
    }

    /**
     * Add representation.
     *
     *
     * @return Pfo
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
        foreach ($this->getEventPfos() as $eventPfo) {
            $events[] = $eventPfo->getEvent();
        }

        return $events;
    }

    /**
     * Set isShared.
     *
     * @param bool $isShared
     *
     * @return Pfo
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
     * @return Pfo
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
     * @return Pfo
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
     * Set deletedBy.
     *
     *
     * @return Pfo
     */
    public function setDeletedBy(\PostparcBundle\Entity\User $deletedBy = null) {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    /**
     * Get deletedBy.
     *
     * @return \PostparcBundle\Entity\User
     */
    public function getDeletedBy() {
        return $this->deletedBy;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return Pfo
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
     * @return Pfo
     */
    public function setUpdated(\DateTimeInterface $updated) {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Add eventPfo.
     *
     *
     * @return Pfo
     */
    public function addEventPfo(\PostparcBundle\Entity\EventPfos $eventPfo) {
        $this->eventPfos[] = $eventPfo;

        return $this;
    }

    /**
     * Remove eventPfo.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventPfo(\PostparcBundle\Entity\EventPfos $eventPfo) {
        return $this->eventPfos->removeElement($eventPfo);
    }

    /**
     * Get eventPfos.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventPfos() {
        return $this->eventPfos;
    }

    public function getEmailsArray() {
        $emails = [];
        if (count($this->getPreferedEmails()) > 0) {
            foreach ($this->getPreferedEmails() as $email) {
                $emails[] = $email->__toString();
            }
        } elseif ($this->getEmail()) {
            $emails[] = $this->getEmail()->__toString();
        } elseif ($this->getOrganization() && $this->getOrganization()->getCoordinate() && $this->getOrganization()->getCoordinate()->getEmail()) {
            $emails[] = $this->getOrganization()->getCoordinate()->getEmail()->__toString();
        }

        return $emails;
    }


    /**
     * Set assistantName.
     *
     * @param string|null $assistantName
     *
     * @return Pfo
     */
    public function setAssistantName($assistantName = null)
    {
        $this->assistantName = $assistantName;

        return $this;
    }

    /**
     * Get assistantName.
     *
     * @return string|null
     */
    public function getAssistantName()
    {
        return $this->assistantName;
    }

    /**
     * Set assistantPhone.
     *
     * @param string|null $assistantPhone
     *
     * @return Pfo
     */
    public function setAssistantPhone($assistantPhone = null)
    {
        $this->assistantPhone = preg_replace('/\D+/', '', $assistantPhone);

        return $this;
    }

    /**
     * Get assistantPhone.
     *
     * @return string|null
     */
    public function getAssistantPhone()
    {
        return wordwrap(preg_replace('/\D+/', '', $this->assistantPhone), 2, ' ', true);

    }

    /**
     * Add eventAlert.
     *
     * @param \PostparcBundle\Entity\EventAlert $eventAlert
     *
     * @return Pfo
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
