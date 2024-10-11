<?php

namespace PostparcBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Doctrine\ORM\Mapping as ORM;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * Service.
 *
 * @ORM\Table(name="service", indexes={@ORM\Index(name="service_slugs", columns={"slug"})})
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\ServiceRepository")
 * @Gedmo\Loggable
 */
class Service
{
    use EntityTimestampableTrait;
    use EntityBlameableTrait;

    /**
     * @var int
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="servicesCreatedBy" )
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    private $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="servicesUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    private $updatedBy;

    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="service", cascade={"remove", "persist"})
     */
    private $pfos;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="service", cascade={"persist"})
     */
    private $representations;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
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
     * @return Service
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Service
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
     * @return Service
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
     * Constructor.
     */
    public function __construct()
    {
        $this->pfos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->representations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add pfo.
     *
     *
     * @return Service
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
     * @return Service
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
}
