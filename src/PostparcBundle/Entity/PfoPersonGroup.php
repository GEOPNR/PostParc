<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\PfoPersonGroupRepository")
 * @ORM\Table(name="pfo_person_group")
 * @UniqueEntity(
 *      {"pfo", "group"},
 *      errorPath="pfo",
 *     message="This association already exist in database."
 * )
 * @UniqueEntity(
 *      {"person", "group"},
 *      errorPath="person",
 *      message="This association already exist in database."
 * )
 */
class PfoPersonGroup
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Pfo", inversedBy="pfoPersonGroups", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $pfo;

    /**
     * @ORM\ManyToOne(targetEntity="Person", inversedBy="pfoPersonGroups", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="pfoPersonGroups", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $group;

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
     * Set pfo.
     *
     *
     * @return PfoPersonGroup
     */
    public function setPfo(\PostparcBundle\Entity\Pfo $pfo = null)
    {
        $this->pfo = $pfo;

        return $this;
    }

    /**
     * Get pfo.
     *
     * @return \PostparcBundle\Entity\Pfo
     */
    public function getPfo()
    {
        return $this->pfo;
    }

    /**
     * Set person.
     *
     *
     * @return PfoPersonGroup
     */
    public function setPerson(\PostparcBundle\Entity\Person $person = null)
    {
        $this->person = $person;

        return $this;
    }

    /**
     * Get person.
     *
     * @return \PostparcBundle\Entity\Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * Set group.
     *
     *
     * @return PfoPersonGroup
     */
    public function setGroup(\PostparcBundle\Entity\Group $group = null)
    {
        $this->group = $group;

        return $this;
    }

    /**
     * Get group.
     *
     * @return \PostparcBundle\Entity\Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Get Personn Associate.
     *
     * @return \PostparcBundle\Entity\Person
     */
    public function getPersonAssociate()
    {
        if ($this->getPerson()) {
            return $this->getPerson();
        } else {
            return $this->getPfo()->getPerson();
        }
    }
}
