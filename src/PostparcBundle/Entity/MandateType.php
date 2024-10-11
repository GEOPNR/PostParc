<?php

namespace PostparcBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Doctrine\ORM\Mapping as ORM;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * MandateType.
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="mandate_type", indexes={@ORM\Index(name="mandateType_slugs", columns={"slug"})})
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\MandateTypeRepository")
 * @Gedmo\Loggable
 */
class MandateType
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
     * @var int
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", length=255, nullable=true)
     */
    private $root;

    /**
     * @var int
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer", length=255)
     */
    private $lft;

    /**
     * @var int
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer", length=255, nullable=true)
     */
    private $rgt;

    /**
     * @var int
     * @Gedmo\TreeLevel
     * @ORM\Column(name="level", type="integer", length=255, nullable=true)
     */
    private $level;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="MandateType", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="MandateType", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="mandateTypesCreatedBy" )
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    private $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="mandateTypesUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    private $updatedBy;

    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="mandateType")
     */
    private $representations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->representations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return type
     */
    public function __toString()
    {
        return $this->name;
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
     * Set name.
     *
     * @param string $name
     *
     * @return MandateType
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
     * @return MandateType
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
     * @return MandateType
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
     * Set root.
     *
     * @param int|null $root
     *
     * @return MandateType
     */
    public function setRoot($root = null)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root.
     *
     * @return int|null
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * Set lft.
     *
     * @param int $lft
     *
     * @return MandateType
     */
    public function setLft($lft)
    {
        $this->lft = $lft;

        return $this;
    }

    /**
     * Get lft.
     *
     * @return int
     */
    public function getLft()
    {
        return $this->lft;
    }

    /**
     * Set rgt.
     *
     * @param int|null $rgt
     *
     * @return MandateType
     */
    public function setRgt($rgt = null)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return int|null
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set level.
     *
     * @param int|null $level
     *
     * @return MandateType
     */
    public function setLevel($level = null)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level.
     *
     * @return int|null
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set parent.
     *
     * @param \PostparcBundle\Entity\MandateType|null $parent
     *
     * @return MandateType
     */
    public function setParent(\PostparcBundle\Entity\MandateType $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \PostparcBundle\Entity\MandateType|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add child.
     *
     *
     * @return MandateType
     */
    public function addChild(\PostparcBundle\Entity\MandateType $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeChild(\PostparcBundle\Entity\MandateType $child)
    {
        return $this->children->removeElement($child);
    }

    /**
     * Get children.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }
}
