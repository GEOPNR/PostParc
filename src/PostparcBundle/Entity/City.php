<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\CityRepository")
 * @ORM\Table(name="city", indexes={@ORM\Index(name="city_names", columns={"name"}), @ORM\Index(name="city_insees", columns={"insee"})})
 * @UniqueEntity("insee")
 * @Gedmo\Loggable
 */
class City
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
     * @Gedmo\Slug(fields={"name", "zipCode"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @Gedmo\Slug(fields={"department"})
     * @ORM\Column(length=128, unique=false)
     */
    private $slugDepartment;

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
     * @ORM\Column(name="zip_code", type="string", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $zipCode;

    /**
     * @var string
     *
     * @ORM\Column(name="insee", type="string", length=10, nullable=true)
     * @Gedmo\Versioned
     */
    private $insee;

    /**
     * @var string
     *
     * @ORM\Column(name="department", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $department;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $country = 'FRANCE';

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active", type="boolean", length=255, options={"default" = "1"})
     * @Gedmo\Versioned
     */
    private $isActive = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="coordinate", type="string", length=255, nullable=true)
     */
    private $coordinate;

    /**
     * @ORM\OneToMany(targetEntity="Coordinate", mappedBy="city", cascade={"persist"})
     */
    private $coordinates;

    /**
     * @ORM\ManyToMany(targetEntity="Territory", mappedBy="cities", cascade={"persist"})
     */
    public $territories;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="citiesCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="citiesUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="connectingCity", cascade={ "persist"})
     */
    private $pfos;

    /**
     * @ORM\OneToMany(targetEntity="Person", mappedBy="birthLocation", cascade={ "persist"})
     */
    private $personsBirthIn;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->coordinates = new \Doctrine\Common\Collections\ArrayCollection();
        $this->territories = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pfos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->personsBirthIn = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return type
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $return = '' . $this->formatForAddress($this->name);
        if ($this->getZipCode() !== '' && $this->getZipCode() !== '0') {
            $return .= ' (' . $this->getZipCode() . ')';
        }

        return $return;
    }

    /**
     * @return string
     */
    public function getLabelForSelect()
    {
        $return = '' . $this->formatForAddress($this->name);
        if ($this->getZipCode() !== '' && $this->getZipCode() !== '0') {
            $return .= ' (' . $this->getZipCode() . ')';
        }

        return $return;
    }

    /**
     * @return string
     */
    public function getListTerritories()
    {
        $return = '';
        $separator = '';
        foreach ($this->getTerritories() as $territory) {
            $return .= $separator . $territory->getName();
            $separator = ', ';
        }

        return $return;
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
     * @return City
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return City
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
     * Set name.
     *
     * @param string $name
     *
     * @return City
     */
    public function setName($name)
    {
        $this->name = $this->formatForAddress($name);

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->formatForAddress($this->name);
    }

    /**
     * Set zipCode.
     *
     * @param string $zipCode
     *
     * @return City
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * Get zipCode.
     *
     * @return string
     */
    public function getZipCode()
    {
        return str_pad($this->zipCode, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Set department.
     *
     * @param string $department
     *
     * @return City
     */
    public function setDepartment($department)
    {
        $this->department = $this->formatForAddress($department);

        return $this;
    }

    /**
     * Get department.
     *
     * @return string
     */
    public function getDepartment()
    {
        return $this->formatForAddress($this->department);
    }

    /**
     * Set country.
     *
     * @param string $country
     *
     * @return City
     */
    public function setCountry($country)
    {
        $this->country = strtoupper($country);

        return $this;
    }

    /**
     * Get country.
     *
     * @return string
     */
    public function getCountry()
    {
        return strtoupper($this->country);
    }

    /**
     * Set isActive.
     *
     * @param bool $isActive
     *
     * @return City
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Add coordinate.
     *
     *
     * @return City
     */
    public function addCoordinate(\PostparcBundle\Entity\Coordinate $coordinate)
    {
        $this->coordinates[] = $coordinate;

        return $this;
    }

    /**
     * Remove coordinate.
     */
    public function removeCoordinate(\PostparcBundle\Entity\Coordinate $coordinate)
    {
        $this->coordinates->removeElement($coordinate);
    }

    /**
     * Get coordinates.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    /**
     * Add territory.
     *
     *
     * @return City
     */
    public function addTerritory(\PostparcBundle\Entity\Territory $territory)
    {
        if ($this->territories->contains($territory)) {
            return;
        }

        $this->territories[] = $territory;

        $territory->addCity($this);

        //return $this;
    }

    /**
     * Remove territory.
     */
    public function removeTerritory(\PostparcBundle\Entity\Territory $territory)
    {
        if (!$this->territories->contains($territory)) {
            return;
        }
        $this->territories->removeElement($territory);
        $territory->removeCity($this);
    }

    /**
     * Get territories.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritories()
    {
        return $this->territories;
    }

    /**
     * Add pfo.
     *
     *
     * @return City
     */
    public function addPfo(\PostparcBundle\Entity\Pfo $pfo)
    {
        $this->pfos[] = $pfo;

        return $this;
    }

    /**
     * Remove pfo.
     */
    public function removePfo(\PostparcBundle\Entity\Pfo $pfo)
    {
        $this->pfos->removeElement($pfo);
    }

    /**
     * Get pfos.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfos()
    {
        return $this->pfos;
    }

    /**
     * Set insee.
     *
     * @param string $insee
     *
     * @return City
     */
    public function setInsee($insee)
    {
        $this->insee = $insee;

        return $this;
    }

    /**
     * Get insee.
     *
     * @return string
     */
    public function getInsee()
    {
        return $this->insee;
    }

    /**
     * Set coordinate.
     *
     * @param string|null $coordinate
     *
     * @return City
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
     * Add personsBirthIn.
     *
     *
     * @return City
     */
    public function addPersonsBirthIn(\PostparcBundle\Entity\Person $personsBirthIn)
    {
        $this->personsBirthIn[] = $personsBirthIn;

        return $this;
    }

    /**
     * Remove personsBirthIn.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removePersonsBirthIn(\PostparcBundle\Entity\Person $personsBirthIn)
    {
        return $this->personsBirthIn->removeElement($personsBirthIn);
    }

    /**
     * Get personsBirthIn.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonsBirthIn()
    {
        return $this->personsBirthIn;
    }

    /**
     * Set slugDepartment.
     *
     * @param string $slugDepartment
     *
     * @return City
     */
    public function setSlugDepartment($slugDepartment)
    {
        $this->slugDepartment = $slugDepartment;

        return $this;
    }

    /**
     * Get slugDepartment.
     *
     * @return string
     */
    public function getSlugDepartment()
    {
        return $this->slugDepartment;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return City
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
     * @return City
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    private function formatForAddress($name)
    {
        $name = str_replace('-', ' ', $name);
        $name = mb_strtoupper($name, 'UTF-8');
        return $this->stripAccents($name);
    }

    private function stripAccents($str)
    {
        $a = ['À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή'];
        $b = ['A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η'];

        return str_replace($a, $b, $str);
    }
}
