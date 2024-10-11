<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * Tag.
 *
 * @Gedmo\Tree(type="nested")
 * @ORM\Table(name="tag", indexes={@ORM\Index(name="tag_slugs", columns={"slug"})})
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\TagRepository")
 */
class Tag
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
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="tagsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="tagsUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

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
     * @ORM\ManyToOne(targetEntity="Tag", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="parent")
     * @ORM\OrderBy({"lft" = "ASC"})
     */
    private $children;

    /**
     * @ORM\ManyToMany(targetEntity="Organization", mappedBy="tags")
     */
    private $organizations;

    /**
     * @ORM\ManyToMany(targetEntity="Pfo", mappedBy="tags")
     */
    private $pfos;

    /**
     * @ORM\ManyToMany(targetEntity="Person", mappedBy="tags")
     */
    private $persons;

    /**
     * @ORM\ManyToMany(targetEntity="Event", mappedBy="tags")
     */
    private $events;

    /**
     * @ORM\ManyToMany(targetEntity="Representation", mappedBy="tags")
     */
    private $representations;

    /**
     * @ORM\ManyToMany(targetEntity="Note", mappedBy="tags")
     */
    private $notes;

    /**
     * @return type
     */
    public function __toString()
    {
        return $this->name;
    }

    public function __construct()
    {
        $this->organizations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pfos = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Tag
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
     * @return Tag
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
     * Add organization.
     *
     *
     * @return Tag
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
     * Set root.
     *
     * @param int|null $root
     *
     * @return Tag
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
     * @return Tag
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
     * @return Tag
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
     * @return Tag
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
     * @param \PostparcBundle\Entity\Tag|null $parent
     *
     * @return Tag
     */
    public function setParent(\PostparcBundle\Entity\Tag $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return \PostparcBundle\Entity\Tag|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add child.
     *
     *
     * @return Tag
     */
    public function addChild(\PostparcBundle\Entity\Tag $child)
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
    public function removeChild(\PostparcBundle\Entity\Tag $child)
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
     * Add pfo.
     *
     *
     * @return Tag
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
     * Add event.
     *
     *
     * @return Tag
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
     * Add person.
     *
     *
     * @return Tag
     */
    public function addPerson(\PostparcBundle\Entity\Person $person)
    {
        $this->persons[] = $person;

        return $this;
    }

    /**
     * Remove person.
     */
    public function removePerson(\PostparcBundle\Entity\Person $person)
    {
        $this->persons->removeElement($person);
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
     * Add representation.
     *
     *
     * @return Tag
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
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
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
     * Add note.
     *
     *
     * @return Tag
     */
    public function addNote(\PostparcBundle\Entity\Note $note)
    {
        $this->notes[] = $note;

        return $this;
    }

    /**
     * Remove note.
     *
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeNote(\PostparcBundle\Entity\Note $note)
    {
        return $this->notes->removeElement($note);
    }

    /**
     * Get notes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotes()
    {
        return $this->notes;
    }
}
