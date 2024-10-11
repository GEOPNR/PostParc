<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityNameTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\AdditionalFunctionRepository")
 * @ORM\Table(name="additional_function", indexes={@ORM\Index(name="additionalFunction_slugs", columns={"slug"})})
 * @Gedmo\Loggable
 */
class AdditionalFunction
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
     * @var string
     *
     * @ORM\Column(name="women_name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $womenName;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="additionalFunctionsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="additionalFunctionsUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="additionalFunction")
     */
    private $pfos;

    /**
     * @return string
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->pfos = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return AdditionnalFunction
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set womenName.
     *
     * @param string $womenName
     *
     * @return AdditionnalFunction
     */
    public function setWomenName($womenName)
    {
        $this->womenName = $womenName;

        return $this;
    }

    /**
     * Get womenName.
     *
     * @return string
     */
    public function getWomenName()
    {
        return $this->womenName;
    }

    /**
     * Add pfo.
     *
     *
     * @return AdditionnalFunction
     */
    public function addPfo(\PostparcBundle\Entity\Pfo $pfo)
    {
        $this->pfos[] = $pfo;

        return $this;
    }

    /**
     * Remove pfo.
     */
    public function removePfo(\PostparcBundle\Entity\Pfo $pfo)
    {
        $this->pfos->removeElement($pfo);
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
}
