<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;
use PostparcBundle\Entity\Traits\EntitySoftDeletableTrait;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\TerritoryRepository")
 * @ORM\Table(name="territory", indexes={@ORM\Index(name="territory_slugs", columns={"slug"})})
 * @Gedmo\Loggable
 */
class Territory
{
    use EntityTimestampableTrait;
    use EntityBlameableTrait;
    use EntitySoftDeletableTrait;

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
     * @ORM\ManyToOne(targetEntity="Territory", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Territory", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_shared", type="boolean", options={"default" = "0"})
     * @Gedmo\Versioned
     */
    protected $isShared = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_editable_by_other_entities", type="boolean", options={"default" = "0"})
     * @Gedmo\Versioned
     */
    protected $isEditableByOtherEntities = false;

    /**
     * @ORM\ManyToMany(targetEntity="City", inversedBy="territories", cascade={"persist"})
     * @ORM\JoinTable(name="territories_cities")
     */
    protected $cities;

    /**
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="TerritoryType", inversedBy="territories")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $territoryType;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="territories")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="territoriesCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;
    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="territoriesUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->cities = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return strlen($this->name) !== 0 ? $this->name : ' ';
    }

    public function getCompletName()
    {
        $completName = $this->name;
        if ($this->getParent() && $this->getParent()->getId() != $this->id) {
            $completName = $this->getParent()->getCompletName() . ' > ' . $completName;
        }
        return $completName;
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
     * @return Territory
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
     * @return Territory
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
     * @return Territory
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
     * Set root.
     *
     * @param int $root
     *
     * @return Territory
     */
    public function setRoot($root)
    {
        $this->root = $root;

        return $this;
    }

    /**
     * Get root.
     *
     * @return int
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
     * @return Territory
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
     * @param int $rgt
     *
     * @return Territory
     */
    public function setRgt($rgt)
    {
        $this->rgt = $rgt;

        return $this;
    }

    /**
     * Get rgt.
     *
     * @return int
     */
    public function getRgt()
    {
        return $this->rgt;
    }

    /**
     * Set level.
     *
     * @param int $level
     *
     * @return Territory
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * Get level.
     *
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    public function setParent(Territory $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @return object
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add city.
     *
     *
     * @return Territory
     */
    public function addCity(\PostparcBundle\Entity\City $city)
    {
        if ($this->cities->contains($city)) {
            return;
        }

        $this->cities[] = $city;
        $city->addTerritory($this);

        return $this;
    }

    /**
     * Remove city.
     */
    public function removeCity(\PostparcBundle\Entity\City $city)
    {
        if (!$this->cities->contains($city)) {
            return;
        }
        $this->cities->removeElement($city);
    }

    /**
     * Get cities.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCities()
    {
        return $this->cities;
    }

    /**
     * Add child.
     *
     *
     * @return Territory
     */
    public function addChild(\PostparcBundle\Entity\Territory $child)
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     *
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeChild(\PostparcBundle\Entity\Territory $child)
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

    /**
     * Set territoryType.
     *
     * @param \PostparcBundle\Entity\TerritoryType|null $territoryType
     *
     * @return Territory
     */
    public function setTerritoryType(\PostparcBundle\Entity\TerritoryType $territoryType = null)
    {
        $this->territoryType = $territoryType;

        return $this;
    }

    /**
     * Get territoryType.
     *
     * @return \PostparcBundle\Entity\TerritoryType|null
     */
    public function getTerritoryType()
    {
        return $this->territoryType;
    }

    /**
     * Set isShared.
     *
     * @param bool $isShared
     *
     * @return Territory
     */
    public function setIsShared($isShared)
    {
        $this->isShared = $isShared;

        return $this;
    }

    /**
     * Get isShared.
     *
     * @return bool
     */
    public function getIsShared()
    {
        return $this->isShared;
    }

    /**
     * Set entity.
     *
     * @param \PostparcBundle\Entity\Entity|null $entity
     *
     * @return Territory
     */
    public function setEntity(\PostparcBundle\Entity\Entity $entity = null)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity.
     *
     * @return \PostparcBundle\Entity\Entity|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set isEditableByOtherEntities.
     *
     * @param bool $isEditableByOtherEntities
     *
     * @return Territory
     */
    public function setIsEditableByOtherEntities($isEditableByOtherEntities)
    {
        $this->isEditableByOtherEntities = $isEditableByOtherEntities;

        return $this;
    }

    /**
     * Get isEditableByOtherEntities.
     *
     * @return bool
     */
    public function getIsEditableByOtherEntities()
    {
        return $this->isEditableByOtherEntities;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return Territory
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
     * @return Territory
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;

        return $this;
    }
}
