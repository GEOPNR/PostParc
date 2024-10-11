<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;
use PostparcBundle\Entity\Traits\EntitySoftDeletableTrait;

/**
 * Entity.
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="entity")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\EntityRepository")
 * @Gedmo\Loggable
 */
class Entity
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
     * @var array
     *
     * @ORM\Column(name="configs", type="json_array", nullable=true)
     */
    private $configs;

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
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Entity", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @var string
     *
     * @ORM\Column(name="coordinate", type="string", length=255, nullable=true)
     */
    private $coordinate;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="entity")
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity="DocumentTemplate", mappedBy="entity")
     */
    private $documentTemplates;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="entity")
     */
    private $events;

    /**
     * @ORM\OneToMany(targetEntity="Organization", mappedBy="entity")
     */
    private $organizations;

    /**
     * @ORM\OneToMany(targetEntity="Person", mappedBy="entity")
     */
    private $persons;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="entity")
     */
    private $pfos;

    /**
     * @ORM\OneToMany(targetEntity="PrintFormat", mappedBy="entity")
     */
    private $printFormats;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="entity")
     */
    private $representations;

    /**
     * @ORM\OneToMany(targetEntity="Group", mappedBy="entity")
     */
    private $groups;

    /**
     * @ORM\OneToMany(targetEntity="SearchList", mappedBy="entity")
     */
    private $searchLists;

    /**
     * @ORM\OneToMany(targetEntity="Territory", mappedBy="entity")
     */
    private $territories;

    /**
     * @ORM\OneToMany(targetEntity="ReaderLimitation", mappedBy="entity")
     */
    private $readerLimitations;

    /**
     * @ORM\OneToOne(targetEntity="PersonnalFieldsRestriction", mappedBy="entity")
     */
    private $personnalFieldsRestriction;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->configs = [
        'use_massiveMail' => false,
        'max_email_per_month' => 600,
        'domains_alowed' => [],
        'use_event_module' => true,
        'use_representation_module' => true,
        'use_readerLimitations_module' => true,
        'show_SharedContents' => true,
        'shared_contents' => true,
        'use_sendInBlue_module' => false,
        'sendInBlue_apiKey' => '',
        ];
    }

    /**
     * @return type
     */
    public function __toString()
    {
        return strlen($this->name) !== 0 ? $this->name : ' ';
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
     * @return Entity
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
     * @return Entity
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
     * @return Entity
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
     * @return Entity
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
     * @return Entity
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
     * @return Entity
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
     * @param \PostparcBundle\Entity\Entity|null $parent
     *
     * @return Entity
     */
    public function setParent(\PostparcBundle\Entity\Entity $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \PostparcBundle\Entity\Entity|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add child.
     *
     *
     * @return Entity
     */
    public function addChild(\PostparcBundle\Entity\Entity $child)
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
    public function removeChild(\PostparcBundle\Entity\Entity $child)
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
     * Add user.
     *
     *
     * @return Entity
     */
    public function addUser(\PostparcBundle\Entity\User $user)
    {
        $this->users[] = $user;

        return $this;
    }

    /**
     * Remove user.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeUser(\PostparcBundle\Entity\User $user)
    {
        return $this->users->removeElement($user);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add documentTemplate.
     *
     *
     * @return Entity
     */
    public function addDocumentTemplate(\PostparcBundle\Entity\DocumentTemplate $documentTemplate)
    {
        $this->documentTemplates[] = $documentTemplate;

        return $this;
    }

    /**
     * Remove documentTemplate.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeDocumentTemplate(\PostparcBundle\Entity\DocumentTemplate $documentTemplate)
    {
        return $this->documentTemplates->removeElement($documentTemplate);
    }

    /**
     * Get documentTemplates.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocumentTemplates()
    {
        return $this->documentTemplates;
    }

    /**
     * Add event.
     *
     *
     * @return Entity
     */
    public function addEvent(\PostparcBundle\Entity\Event $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEvent(\PostparcBundle\Entity\Event $event)
    {
        return $this->events->removeElement($event);
    }

    /**
     * Get events.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Add organization.
     *
     *
     * @return Entity
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
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
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
     * Add person.
     *
     *
     * @return Entity
     */
    public function addPerson(\PostparcBundle\Entity\Person $person)
    {
        $this->persons[] = $person;

        return $this;
    }

    /**
     * Remove person.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removePerson(\PostparcBundle\Entity\Person $person)
    {
        return $this->persons->removeElement($person);
    }

    /**
     * Get persons.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersons()
    {
        return $this->persons;
    }

    /**
     * Add pfo.
     *
     *
     * @return Entity
     */
    public function addPfo(\PostparcBundle\Entity\Pfo $pfo)
    {
        $this->pfos[] = $pfo;

        return $this;
    }

    /**
     * Remove pfo.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removePfo(\PostparcBundle\Entity\Pfo $pfo)
    {
        return $this->pfos->removeElement($pfo);
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
     * Add printFormat.
     *
     *
     * @return Entity
     */
    public function addPrintFormat(\PostparcBundle\Entity\PrintFormat $printFormat)
    {
        $this->printFormats[] = $printFormat;

        return $this;
    }

    /**
     * Remove printFormat.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removePrintFormat(\PostparcBundle\Entity\PrintFormat $printFormat)
    {
        return $this->printFormats->removeElement($printFormat);
    }

    /**
     * Get printFormats.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrintFormats()
    {
        return $this->printFormats;
    }

    /**
     * Add representation.
     *
     *
     * @return Entity
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
     * Add group.
     *
     *
     * @return Entity
     */
    public function addGroup(\PostparcBundle\Entity\Group $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Remove group.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeGroup(\PostparcBundle\Entity\Group $group)
    {
        return $this->groups->removeElement($group);
    }

    /**
     * Get groups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Add searchList.
     *
     *
     * @return Entity
     */
    public function addSearchList(\PostparcBundle\Entity\SearchList $searchList)
    {
        $this->searchLists[] = $searchList;

        return $this;
    }

    /**
     * Remove searchList.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeSearchList(\PostparcBundle\Entity\SearchList $searchList)
    {
        return $this->searchLists->removeElement($searchList);
    }

    /**
     * Get searchLists.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSearchLists()
    {
        return $this->searchLists;
    }

    /**
     * Add territory.
     *
     *
     * @return Entity
     */
    public function addTerritory(\PostparcBundle\Entity\Territory $territory)
    {
        $this->territories[] = $territory;

        return $this;
    }

    /**
     * Remove territory.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTerritory(\PostparcBundle\Entity\Territory $territory)
    {
        return $this->territories->removeElement($territory);
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
     * Add readerLimitation.
     *
     *
     * @return Entity
     */
    public function addReaderLimitation(\PostparcBundle\Entity\ReaderLimitation $readerLimitation)
    {
        $this->readerLimitations[] = $readerLimitation;

        return $this;
    }

    /**
     * Remove readerLimitation.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeReaderLimitation(\PostparcBundle\Entity\ReaderLimitation $readerLimitation)
    {
        return $this->readerLimitations->removeElement($readerLimitation);
    }

    /**
     * Get readerLimitations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReaderLimitations()
    {
        return $this->readerLimitations;
    }

    /**
     * Set configs.
     *
     * @param array|null $configs
     *
     * @return Entity
     */
    public function setConfigs($configs = null)
    {
        $this->configs = $configs;

        return $this;
    }

    /**
     * Get configs.
     *
     * @return array|null
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * Set deletedBy.
     *
     *
     * @return Entity
     */
    public function setDeletedBy(\PostparcBundle\Entity\User $deletedBy = null)
    {
        $this->deletedBy = $deletedBy;

        return $this;
    }

    /**
     * Get deletedBy.
     *
     * @return \PostparcBundle\Entity\User
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * Set coordinate.
     *
     * @param string $coordinate
     *
     * @return Entity
     */
    public function setCoordinate($coordinate)
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * Get coordinate.
     *
     * @return string
     */
    public function getCoordinate()
    {
        return $this->coordinate;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return Entity
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
     * @return Entity
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Set personnalFieldsRestriction.
     *
     * @param \PostparcBundle\Entity\PersonnalFieldsRestriction|null $personnalFieldsRestriction
     *
     * @return Entity
     */
    public function setPersonnalFieldsRestriction(\PostparcBundle\Entity\PersonnalFieldsRestriction $personnalFieldsRestriction = null)
    {
        $this->personnalFieldsRestriction = $personnalFieldsRestriction;

        return $this;
    }

    /**
     * Get personnalFieldsRestriction.
     *
     * @return \PostparcBundle\Entity\PersonnalFieldsRestriction|null
     */
    public function getPersonnalFieldsRestriction()
    {
        return $this->personnalFieldsRestriction;
    }
}
