<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityNameTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * OrganizationType.
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="organization_type")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\OrganizationTypeRepository")
 * @Gedmo\Loggable
 */
class OrganizationType
{
    use EntityTimestampableTrait;
    use EntityNameTrait;
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
     * @ORM\ManyToOne(targetEntity="OrganizationType", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @ORM\OrderBy({"slug" = "ASC"})
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="OrganizationType", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Organization", mappedBy="organizationType", cascade={"persist"})
     */
    public $organizations;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="organizationTypesCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="organizationTypesUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

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
     * Constructor.
     */
    public function __construct()
    {
        $this->organizations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add organization.
     *
     *
     * @return OrganizationType
     */
    public function addOrganization(\PostparcBundle\Entity\Organization $organization)
    {
        $this->organizations[] = $organization;

        return $this;
    }

    /**
     * Remove organization.
     *
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeOrganization(\PostparcBundle\Entity\Organization $organization)
    {
        return $this->organizations->removeElement($organization);
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
     * Set root.
     *
     * @param int|null $root
     *
     * @return OrganizationType
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
     * @return OrganizationType
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
     * @return OrganizationType
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
     * @return OrganizationType
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
     * @param \PostparcBundle\Entity\OrganizationType|null $parent
     *
     * @return OrganizationType
     */
    public function setParent(\PostparcBundle\Entity\OrganizationType $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \PostparcBundle\Entity\OrganizationType|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add child.
     *
     *
     * @return OrganizationType
     */
    public function addChild(\PostparcBundle\Entity\OrganizationType $child)
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
    public function removeChild(\PostparcBundle\Entity\OrganizationType $child)
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
