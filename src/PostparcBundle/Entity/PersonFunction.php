<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;
use PostparcBundle\Entity\Traits\EntityLockableTrait;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\PersonFunctionRepository")
 * @ORM\Table(name="person_function", indexes={@ORM\Index(name="personFunction_slugs", columns={"slug"})})
 * @Gedmo\Loggable
 */
class PersonFunction
{
    use EntityTimestampableTrait;
    use EntityBlameableTrait;
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
     * @Gedmo\Slug(fields={"name"})
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
     * @ORM\Column(name="women_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $womenName;

    /**
     * @var string
     *
     * @ORM\Column(name="men_particle", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $menParticle;

    /**
     * @var string
     *
     * @ORM\Column(name="women_particle", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $womenParticle;

    /**
     * @var bool
     *
     * @ORM\Column(name="not_print_on_coordinate", type="boolean", options={"default" = "0"})
     * @Gedmo\Versioned
     */
    private $notPrintOnCoordinate = false;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="personFunctionsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="personFunctionsUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="personFunction")
     */
    protected $pfos;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="personFunction")
     */
    protected $representations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->pfos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->representations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return strlen($this->name) !== 0 ? $this->name : ' ';
    }

    /**
     * @return string
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
     * Set id.
     *
     * @param int $id
     *
     * @return PersonFunction
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
     * @return PersonFunction
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
     * @return PersonFunction
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
     * Set womenName.
     *
     * @param string $womenName
     *
     * @return PersonFunction
     */
    public function setWomenName($womenName)
    {
        $this->womenName = $womenName;

        return $this;
    }

    /**
     * Get womenName.
     *
     * @return string
     */
    public function getWomenName()
    {
        return $this->womenName;
    }

    /**
     * Set menParticle.
     *
     * @param string $menParticle
     *
     * @return PersonFunction
     */
    public function setMenParticle($menParticle)
    {
        $this->menParticle = $menParticle;

        return $this;
    }

    /**
     * Get menParticle.
     *
     * @return string
     */
    public function getMenParticle()
    {
        return $this->menParticle;
    }

    /**
     * Set womenParticle.
     *
     * @param string $womenParticle
     *
     * @return PersonFunction
     */
    public function setWomenParticle($womenParticle)
    {
        $this->womenParticle = $womenParticle;

        return $this;
    }

    /**
     * Get womenParticle.
     *
     * @return string
     */
    public function getWomenParticle()
    {
        return $this->womenParticle;
    }

    /**
     * Add pfo.
     *
     *
     * @return PersonFunction
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
     * Add representation.
     *
     *
     * @return PersonFunction
     */
    public function addRepresentation(\PostparcBundle\Entity\Representation $representation)
    {
        $this->representations[] = $representation;

        return $this;
    }

    /**
     * Remove representation.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeRepresentation(\PostparcBundle\Entity\Representation $representation)
    {
        return $this->representations->removeElement($representation);
    }

    /**
     * Get representations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRepresentations()
    {
        return $this->representations;
    }

    /**
     * Set notPrintOnCoordinate.
     *
     * @param bool $notPrintOnCoordinate
     *
     * @return PersonFunction
     */
    public function setNotPrintOnCoordinate($notPrintOnCoordinate)
    {
        $this->notPrintOnCoordinate = $notPrintOnCoordinate;

        return $this;
    }

    /**
     * Get notPrintOnCoordinate.
     *
     * @return bool
     */
    public function getNotPrintOnCoordinate()
    {
        return $this->notPrintOnCoordinate;
    }
}
