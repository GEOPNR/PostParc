<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\CoordinateRepository")
 * @ORM\Table(name="coordinates")
 * @Gedmo\Loggable
 */
class Coordinate
{
    use EntityTimestampableTrait;
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
     * @ORM\Column(name="address_line_1", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $addressLine1;

    /**
     * @var string
     *
     * @ORM\Column(name="address_line_2", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $addressLine2;

    /**
     * @var string
     *
     * @ORM\Column(name="address_line_3", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $addressLine3;

    /**
     * @var string
     *
     * @ORM\Column(name="cedex", type="string", length=32, nullable=true)
     * @Gedmo\Versioned
     */
    private $cedex;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="string", length=50, nullable=true)
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
     * @ORM\Column(name="coordinate", type="string", length=255, nullable=true)
     */
    private $coordinate;

    /**
     * @var string
     *
     * @ORM\Column(name="facebookAccount", type="string", length=255, nullable=true)
     */
    private $facebookAccount;

    /**
     * @var string
     *
     * @ORM\Column(name="twitterAccount", type="string", length=255, nullable=true)
     */
    private $twitterAccount;

    /**
     * @var string
     *
     * @ORM\Column(name="phoneCode", type="string", length=10, nullable=true)
     */
    private $phoneCode;

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
     * @ORM\Column(name="web_site", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $webSite;

    /**
     * @ORM\ManyToOne(targetEntity="City", inversedBy="coordinates")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $city;

    /**
     * @ORM\OneToMany(targetEntity="Person", mappedBy="coordinate", cascade={"remove", "persist"})
     */
    private $persons;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="coordinate", cascade={"remove", "persist"})
     */
    private $users;

    /**
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="coordinateEmails", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $email;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="coordinatesCreatedBy", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="coordinatesUpdatedBy", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\OneToOne(targetEntity="Organization", mappedBy="coordinate", cascade={"remove", "persist"}, fetch="EAGER")
     */
    private $organization;

    /**
     * @ORM\OneToOne(targetEntity="Representation", mappedBy="coordinate", cascade={"remove", "persist"}, fetch="EAGER")
     */
    private $representation;

    /**
     * @ORM\OneToOne(targetEntity="Event", mappedBy="coordinate", cascade={"remove", "persist"}, fetch="EAGER")
     */
    private $event;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="preferedCoordinateAddress", cascade={"remove", "persist"})
     */
    private $pfosPreferedCoordinateAddress;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="preferedCoordinateAddress", cascade={"persist"})
     */
    private $representationsPreferedCoordinateAddress;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->persons = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $return = ' ' . $this->addressLine1;
        if ($this->getCity()) {
            $return .= ' (' . $this->getCity()->getZipCode() . ' ' . $this->getCity()->getName() . ')';
        }

        return $return;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function getApiFormated($format = 'object')
    {
        return [
            'id' => $this->id,
            'addressLine1' => $this->addressLine1,
            'addressLine2' => $this->addressLine2,
            'addressLine3' => $this->addressLine3,
            'cedex' => $this->cedex,
            'phone' => $this->phone,
            'mobilePhone' => $this->mobilePhone,
            'coordinate' => $this->coordinate,
            'facebookAccount' => $this->facebookAccount,
            'twitterAccount' => $this->twitterAccount,
            'fax' => $this->fax,
            'webSite' => $this->webSite,
            'city' => $this->city ? $this->city->__toString() : null,
            'email' => $this->email ? $this->email->__toString() : null,
        ];
    }

    /**
     * @return string
     */
    public function getFormatedAddress($personnalFieldsRestriction = [])
    {
        $return = '';
        if (!in_array('addressLine1', $personnalFieldsRestriction)) {
            $return .= $this->getAddressLine1();
        }
        if (strlen(trim($this->getAddressLine2())) && !in_array('addressLine2', $personnalFieldsRestriction)) {
            $return .= '<br/>' . $this->getAddressLine2();
        }
        if (strlen(trim($this->getAddressLine3())) && !in_array('addressLine3', $personnalFieldsRestriction)) {
            $return .= '<br/>' . $this->getAddressLine3();
        }
        if ($this->getCity() && !in_array('city', $personnalFieldsRestriction)) {
            $return .= '<br/>' . $this->getCity()->getZipCode() . ' ' . $this->getCity()->getName();
            if ($this->cedex !== '' && $this->cedex !== '0') {
                $return .= ' ' . $this->cedex;
            }
        }

        return nl2br(str_replace('<br />', '', $return));
    }

    /**
     * @param Request $postRequest
     *
     * @return string
     */
    public function getPrintForSticker($postRequest, $user, $fromPfo = 0, $personnalFieldsRestriction = [])
    {
        $content = '';
        $separator = '';
        if ($postRequest->has('coordinate')) {
            $tabFields = $postRequest->get('coordinate');
            // mise en place restriction accès coordonnées personnelles
            foreach ($personnalFieldsRestriction as $restriction) {
                if (array_key_exists($restriction, $tabFields)) {
                    unset($tabFields[$restriction]);
                }
            }
            if (isset($tabFields['addressLine1']) && strlen(trim($this->getAddressLine1()))) {
                $content .= '<br/>' . trim($this->getAddressLine1());
            }
            if (isset($tabFields['addressLine2']) && strlen(trim($this->getAddressLine2()))) {
                $content .= '<br/>' . trim($this->getAddressLine2());
            }
            if (isset($tabFields['addressLine3']) && strlen(trim($this->getAddressLine3()))) {
                $content .= '<br/>' . trim($this->getAddressLine3());
            }

            if ($this->getCity() && (isset($tabFields['zipCode']) || isset($tabFields['city']) || isset($tabFields['cedex']) || isset($tabFields['country']))) {
                $content .= '<br/>';
                if (isset($tabFields['zipCode'])) {
                    $content .= $separator . $this->getCity()->getZipCode();
                    $separator = ' ';
                }
                if (isset($tabFields['city'])) {
                    $content .= $separator . $this->getCity()->getName();
                    $separator = ' ';
                }
                if (isset($tabFields['cedex']) && strlen(trim($this->getCedex()))) {
                    $content .= ' ' . trim($this->getCedex());
                }
                if (isset($tabFields['country']) && ($this->getCity()->getCountry() && !in_array($this->getCity()->getCountry(), ['France', 'FRANCE']))) {
                    $content .= '<br/>' . $this->getCity()->getCountry();
                }
            }
        }

        return nl2br($content);
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
     * Set id.
     *
     * @param int $id
     *
     * @return Coordinate
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set addressLine1.
     *
     * @param string $addressLine1
     *
     * @return Coordinate
     */
    public function setAddressLine1($addressLine1)
    {
        $this->addressLine1 = $addressLine1;

        return $this;
    }

    /**
     * Get addressLine1.
     *
     * @return string
     */
    public function getAddressLine1()
    {
        return $this->addressLine1;
    }

    /**
     * Set addressLine2.
     *
     * @param string $addressLine2
     *
     * @return Coordinate
     */
    public function setAddressLine2($addressLine2)
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    /**
     * Get addressLine2.
     *
     * @return string
     */
    public function getAddressLine2()
    {
        return $this->addressLine2;
    }

    /**
     * Set addressLine3.
     *
     * @param string $addressLine3
     *
     * @return Coordinate
     */
    public function setAddressLine3($addressLine3)
    {
        $this->addressLine3 = $addressLine3;

        return $this;
    }

    /**
     * Get addressLine3.
     *
     * @return string
     */
    public function getAddressLine3()
    {
        return $this->addressLine3;
    }

    /**
     * Set cedex.
     *
     * @param string $cedex
     *
     * @return Coordinate
     */
    public function setCedex($cedex)
    {
        $this->cedex = $cedex;

        return $this;
    }

    /**
     * Get cedex.
     *
     * @return string
     */
    public function getCedex()
    {
        return $this->cedex;
    }

    /**
     * Set phone.
     *
     * @param string $phone
     *
     * @return Coordinate
     */
    public function setPhone($phone)
    {
        $this->phone = preg_replace('/\D+/', '', $phone);

        return $this;
    }

    /**
     * Get phone.
     *
     * @return string
     */
    public function getPhone()
    {
        return wordwrap(preg_replace('/\D+/', '', $this->phone), 2, ' ', true);
        //return $this->phone;
    }

    /**
     * Set mobilePhone.
     *
     * @param string $mobilePhone
     *
     * @return Coordinate
     */
    public function setMobilePhone($mobilePhone)
    {
        $this->mobilePhone = preg_replace('/\D+/', '', $mobilePhone);

        return $this;
    }

    /**
     * Get mobilePhone.
     *
     * @return string
     */
    public function getMobilePhone()
    {
        return wordwrap(preg_replace('/\D+/', '', $this->mobilePhone), 2, ' ', true);
        //return $this->mobilePhone;
    }

    /**
     * Set fax.
     *
     * @param string $fax
     *
     * @return Coordinate
     */
    public function setFax($fax)
    {
        $this->fax = preg_replace('/\D+/', '', $fax);

        return $this;
    }

    /**
     * Get fax.
     *
     * @return string
     */
    public function getFax()
    {
        return wordwrap(preg_replace('/\D+/', '', $this->fax), 2, ' ', true);
        //return $this->fax;
    }

    /**
     * Set webSite.
     *
     * @param string $webSite
     *
     * @return Coordinate
     */
    public function setWebSite($webSite)
    {
        $this->webSite = $webSite;

        return $this;
    }

    /**
     * Get webSite.
     *
     * @return string
     */
    public function getWebSite()
    {
        return $this->webSite;
    }

    /**
     * Set city.
     *
     *
     * @return Coordinate
     */
    public function setCity(\PostparcBundle\Entity\City $city = null)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city.
     *
     * @return \PostparcBundle\Entity\City
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Add person.
     *
     *
     * @return Coordinate
     */
    public function addPerson(\PostparcBundle\Entity\Person $person)
    {
        $this->persons[] = $person;

        return $this;
    }

    /**
     * Remove person.
     */
    public function removePerson(\PostparcBundle\Entity\Person $person)
    {
        $this->persons->removeElement($person);
    }

    /**
     * Get persons.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersons()
    {
        return $this->persons;
    }

    /**
     * Set email.
     *
     *
     * @return Coordinate
     */
    public function setEmail(\PostparcBundle\Entity\Email $email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return \PostparcBundle\Entity\Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set organization.
     *
     *
     * @return Coordinate
     */
    public function setOrganization(\PostparcBundle\Entity\Organization $organization = null)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization.
     *
     * @return \PostparcBundle\Entity\Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * Add pfosPreferedCoordinateAddress.
     *
     *
     * @return Coordinate
     */
    public function addPfosPreferedCoordinateAddress(\PostparcBundle\Entity\Pfo $pfosPreferedCoordinateAddress)
    {
        $this->pfosPreferedCoordinateAddress[] = $pfosPreferedCoordinateAddress;

        return $this;
    }

    /**
     * Remove pfosPreferedCoordinateAddress.
     *
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removePfosPreferedCoordinateAddress(\PostparcBundle\Entity\Pfo $pfosPreferedCoordinateAddress)
    {
        return $this->pfosPreferedCoordinateAddress->removeElement($pfosPreferedCoordinateAddress);
    }

    /**
     * Get pfosPreferedCoordinateAddress.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfosPreferedCoordinateAddress()
    {
        return $this->pfosPreferedCoordinateAddress;
    }

    /**
     * Set coordinate.
     *
     * @param string|null $coordinate
     *
     * @return Coordinate
     */
    public function setCoordinate($coordinate = null)
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * Get coordinate.
     *
     * @return string|null
     */
    public function getCoordinate()
    {
        return $this->coordinate;
    }

    /**
     * Set facebookAccount.
     *
     * @param string|null $facebookAccount
     *
     * @return Coordinate
     */
    public function setFacebookAccount($facebookAccount = null)
    {
        $this->facebookAccount = $facebookAccount;

        return $this;
    }

    /**
     * Get facebookAccount.
     *
     * @return string|null
     */
    public function getFacebookAccount()
    {
        return $this->facebookAccount;
    }

    /**
     * Set twitterAccount.
     *
     * @param string|null $twitterAccount
     *
     * @return Coordinate
     */
    public function setTwitterAccount($twitterAccount = null)
    {
        $this->twitterAccount = $twitterAccount;

        return $this;
    }

    /**
     * Get twitterAccount.
     *
     * @return string|null
     */
    public function getTwitterAccount()
    {
        return $this->twitterAccount;
    }

    /**
     * Set representation.
     *
     * @param \PostparcBundle\Entity\Representation|null $representation
     *
     * @return Coordinate
     */
    public function setRepresentation(\PostparcBundle\Entity\Representation $representation = null)
    {
        $this->representation = $representation;

        return $this;
    }

    /**
     * Get representation.
     *
     * @return \PostparcBundle\Entity\Representation|null
     */
    public function getRepresentation()
    {
        return $this->representation;
    }

    /**
     * Set event.
     *
     * @param \PostparcBundle\Entity\Event|null $event
     *
     * @return Coordinate
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
     * Add representationsPreferedCoordinateAddress.
     *
     *
     * @return Coordinate
     */
    public function addRepresentationsPreferedCoordinateAddress(\PostparcBundle\Entity\Representation $representationsPreferedCoordinateAddress)
    {
        $this->representationsPreferedCoordinateAddress[] = $representationsPreferedCoordinateAddress;

        return $this;
    }

    /**
     * Remove representationsPreferedCoordinateAddress.
     */
    public function removeRepresentationsPreferedCoordinateAddress(\PostparcBundle\Entity\Representation $representationsPreferedCoordinateAddress)
    {
        $this->representationsPreferedCoordinateAddress->removeElement($representationsPreferedCoordinateAddress);
    }

    /**
     * Get representationsPreferedCoordinateAddress.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRepresentationsPreferedCoordinateAddress()
    {
        return $this->representationsPreferedCoordinateAddress;
    }

    /**
     * Set phoneCode.
     *
     * @param string $phoneCode
     *
     * @return Coordinate
     */
    public function setPhoneCode($phoneCode)
    {
        $this->phoneCode = $phoneCode;

        return $this;
    }

    /**
     * Get phoneCode.
     *
     * @return string
     */
    public function getPhoneCode()
    {
        return $this->phoneCode;
    }

    /**
     * Add user.
     *
     *
     * @return Coordinate
     */
    public function addUser(\PostparcBundle\Entity\User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user.
     */
    public function removeUser(\PostparcBundle\Entity\User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
