<?php

namespace PostparcBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait EntitySoftDeletableTrait
{
    /**
     * @var datetime
     *
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $deletedBy;

    /**
     * Set deletedAt.
     *
     * @param \DateTime $deletedAt
     *
     * @return Plan
     */
    public function setDeletedAt(\DateTimeInterface $deletedAt)
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    /**
     * Get deletedAt.
     *
     * @return \DateTime
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Set deletedBy.
     *
     *
     * @return DocumentTemplate
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
}
