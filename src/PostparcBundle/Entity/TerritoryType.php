<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * TerritoryType.
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="territory_type", indexes={@ORM\Index(name="territoryType_slugs", columns={"slug"})})
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\TerritoryTypeRepository")
 * @Gedmo\Loggable
 */
class TerritoryType
{
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    public $organizations;
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
     * @ORM\Column(name="name", type="string", length=255, unique=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

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
     * @ORM\ManyToOne(targetEntity="TerritoryType", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="TerritoryType", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Territory", mappedBy="territoryType", cascade={ "persist"})
     */
    public $territories;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="territoryTypesCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;
    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="territoryTypesUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    public function getCompletName()
    {
        $completName = $this->name;
        if ($this->getParent() && $this->getParent()->getId() !== $this->id) {
            $completName = $this->getParent()->getCompletName() . ' > ' . $completName;
        }
        return $completName;
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
     * @return TerritoryType
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
     * Constructor.
     */
    public function __construct()
    {
        $this->organizations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add territory.
     *
     *
     * @return TerritoryType
     */
    public function addTerritory(\PostparcBundle\Entity\Territory $territory)
    {
        $this->territories[] = $territory;

        return $this;
    }

    /**
     * Remove organization.
     *
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTerritory(\PostparcBundle\Entity\Territory $territory)
    {
        return $this->territories->removeElement($territory);
    }

    /**
     * Get organizations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritories()
    {
        return $this->territories;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return TerritoryType
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
     * Set root.
     *
     * @param int|null $root
     *
     * @return TerritoryType
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
     * @return TerritoryType
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
     * @return TerritoryType
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
     * @return TerritoryType
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
     * @param \PostparcBundle\Entity\TerritoryType|null $parent
     *
     * @return TerritoryType
     */
    public function setParent(\PostparcBundle\Entity\TerritoryType $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \PostparcBundle\Entity\TerritoryType|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add child.
     *
     *
     * @return TerritoryType
     */
    public function addChild(\PostparcBundle\Entity\TerritoryType $child)
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
    public function removeChild(\PostparcBundle\Entity\TerritoryType $child)
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
