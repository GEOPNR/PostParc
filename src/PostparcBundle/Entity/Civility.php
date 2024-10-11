<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityNameTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * @ORM\Entity()
 * @ORM\Table(name="civility")
 * @Gedmo\Loggable
 */
class Civility
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
     * @var bool
     *
     * @ORM\Column(name="isFeminine", type="boolean",  options={"default" = "0"})
     */
    private $isFeminine;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="civilitiesCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="civilitiesUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Person", mappedBy="civility")
     */
    private $persons;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->persons = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set id.
     *
     * @param int $id
     *
     * @return Civility
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Add person.
     *
     *
     * @return Civility
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
     * Set isFeminine.
     *
     * @param bool $isFeminine
     *
     * @return Civility
     */
    public function setIsFeminine($isFeminine)
    {
        $this->isFeminine = $isFeminine;

        return $this;
    }

    /**
     * Get isFeminine.
     *
     * @return bool
     */
    public function getIsFeminine()
    {
        return $this->isFeminine;
    }
}
