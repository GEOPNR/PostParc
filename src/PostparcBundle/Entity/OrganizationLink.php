<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * OrganizationLink.
 *
 * @ORM\Table(name="organization_link")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\OrganizationLinkRepository")
 */
class OrganizationLink
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
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @var int
     *
     * @ORM\Column(name="linkType", type="smallint")
     */
    protected $linkType;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="pfosCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="pfosUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="organizationOriginLinks", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $organizationOrigin;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="organizationLinkedLinks",cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organizationLinked;

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
     * Set name.
     *
     * @param string $name
     *
     * @return OrganizationLink
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
     * Set linkType.
     *
     * @param int $linkType
     *
     * @return OrganizationLink
     */
    public function setLinkType($linkType)
    {
        $this->linkType = $linkType;

        return $this;
    }

    /**
     * Get linkType.
     *
     * @return int
     */
    public function getLinkType()
    {
        return $this->linkType;
    }

    /**
     * Set organizationOrigin.
     *
     * @param \PostparcBundle\Entity\Organization|null $organizationOrigin
     *
     * @return OrganizationLink
     */
    public function setOrganizationOrigin(\PostparcBundle\Entity\Organization $organizationOrigin = null)
    {
        $this->organizationOrigin = $organizationOrigin;

        return $this;
    }

    /**
     * Get organizationOrigin.
     *
     * @return \PostparcBundle\Entity\Organization|null
     */
    public function getOrganizationOrigin()
    {
        return $this->organizationOrigin;
    }

    /**
     * Set organizationLinked.
     *
     * @param \PostparcBundle\Entity\Organization|null $organizationLinked
     *
     * @return OrganizationLink
     */
    public function setOrganizationLinked(\PostparcBundle\Entity\Organization $organizationLinked = null)
    {
        $this->organizationLinked = $organizationLinked;

        return $this;
    }

    /**
     * Get organizationLinked.
     *
     * @return \PostparcBundle\Entity\Organization|null
     */
    public function getOrganizationLinked()
    {
        return $this->organizationLinked;
    }
}
