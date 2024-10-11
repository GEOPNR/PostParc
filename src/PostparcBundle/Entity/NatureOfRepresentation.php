<?php

namespace PostparcBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Doctrine\ORM\Mapping as ORM;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * NatureOfRepresentation.
 *
 * @ORM\Table(name="natureOfRepresentation", indexes={@ORM\Index(name="natureOfRepresentation_slugs", columns={"slug"})})
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\NatureOfRepresentationRepository")
 * @Gedmo\Loggable
*/
class NatureOfRepresentation
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="natureOfRepresentation")
     */
    protected $representations;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User" )
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    private $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    private $updatedBy;

    /**
     * Constructor.
     */
    public function __construct()
    {
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
     * Set name.
     *
     * @param string $name
     *
     * @return NatureOfRepresentation
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
     * @return NatureOfRepresentation
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
     * Add representation.
     *
     *
     * @return NatureOfRepresentation
     */
    public function addRepresentation(\PostparcBundle\Entity\Representation $representation)
    {
        $this->representations[] = $representation;

        return $this;
    }

    /**
     * Remove representation.
     */
    public function removeRepresentation(\PostparcBundle\Entity\Representation $representation)
    {
        $this->representations->removeElement($representation);
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
