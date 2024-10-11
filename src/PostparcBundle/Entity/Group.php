<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityNameTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;
use PostparcBundle\Entity\Traits\EntitySoftDeletableTrait;

/**
 * @Gedmo\Tree(type="nested")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\GroupRepository")
 * @ORM\Table(name="groups", indexes={@ORM\Index(name="group_slugs", columns={"slug"})})
 * @Gedmo\Loggable
 */
class Group
{
    use EntityTimestampableTrait;
    use EntityNameTrait;
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
     * @var int
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     */
    private $root;

    /**
     * @var int
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     */
    private $lft;

    /**
     * @var int
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     */
    private $rgt;

    /**
     * @var int
     * @Gedmo\TreeLevel
     * @ORM\Column(name="level", type="integer")
     */
    private $level;

    /**
     * @Gedmo\TreeParent
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Group", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

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
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="groupsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="groupsUpdatedBy")
     * @Gedmo\Blameable(on="update")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\OneToMany(targetEntity="PfoPersonGroup", mappedBy="group", cascade={"persist"})
     */
    protected $pfoPersonGroups;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="groups")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;

    /**
     * @ORM\ManyToMany(targetEntity="Representation", mappedBy="groups")
     */
    private $representations;

    /**
     * @ORM\ManyToMany(targetEntity="Organization", mappedBy="groups")
     */
    private $organizations;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="isPrivate", type="boolean")
     */
    private $isPrivate;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->pfoPersonGroups = new \Doctrine\Common\Collections\ArrayCollection();
        $this->representations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->organizations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return strlen($this->name) !== 0 ? str_repeat(' ', $this->level) . $this->name : ' ';
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
     * @return string
     */
    public function __toStringForSelect()
    {
        return str_repeat('&nbsp; ', $this->level) . $this->name;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function getApiFormated()
    {
        $children = [];
        foreach ($this->getChildren() as $child) {
            $children[] = $child->getApiFormated();
        }

        return [
            'id' => $this->id,
            'level' => $this->level,
            'name' => $this->name,
            'completName' => $this->getCompletName(),
            'root' => $this->root,
            'lft' => $this->lft,
            'rgt' => $this->rgt,
            'slug' => $this->slug,
            '__children' => $children,
        ];
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
     * Set root.
     *
     * @param int $root
     *
     * @return Group
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
     * @return Group
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
     * @return Group
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
     * @return Group
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

    /**
     * @return Group
     */
    public function setParent(Group $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * @return Group
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add pfoPersonGroup.
     *
     *
     * @return Group
     */
    public function addPfoPersonGroup(\PostparcBundle\Entity\PfoPersonGroup $pfoPersonGroup)
    {
        $this->pfoPersonGroups[] = $pfoPersonGroup;

        return $this;
    }

    /**
     * Remove pfoPersonGroup.
     */
    public function removePfoPersonGroup(\PostparcBundle\Entity\PfoPersonGroup $pfoPersonGroup)
    {
        $this->pfoPersonGroups->removeElement($pfoPersonGroup);
    }

    /**
     * Get pfoPersonGroups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfoPersonGroups()
    {
        return $this->pfoPersonGroups;
    }

    /**
     * Add child.
     *
     *
     * @return Group
     */
    public function addChild(\PostparcBundle\Entity\Group $child)
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
    public function removeChild(\PostparcBundle\Entity\Group $child)
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
     * Set isShared.
     *
     * @param bool $isShared
     *
     * @return Group
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
     * @return Group
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
     * @return Group
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
     * Add representation.
     *
     *
     * @return Group
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

    /**
     * Add organization.
     *
     *
     * @return Group
     */
    public function addOrganization(\PostparcBundle\Entity\Organization $organization)
    {
        $this->organizations[] = $organization;

        return $this;
    }

    /**
     * Remove organization.
     */
    public function removeOrganization(\PostparcBundle\Entity\Organization $organization)
    {
        $this->organizations->removeElement($organization);
    }

    /**
     * Get organizations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizations()
    {
        return $this->organizations;
    }

    /**
     * Set isPrivate.
     *
     * @param bool $isPrivate
     *
     * @return Group
     */
    public function setIsPrivate($isPrivate)
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    /**
     * Get isPrivate.
     *
     * @return bool
     */
    public function getIsPrivate()
    {
        return $this->isPrivate;
    }
}
