<?php

namespace PostparcBundle\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait EntityLockableTrait
{
    /**
         * @var datetime
         *
         * @ORM\Column(name="lockedAt", type="datetime", nullable=true)
         */
    private $lockedAt;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $lockedBy;

    /**
     * Set lockedAt.
     *
     * @param \DateTime $lockedAt
     *
     * @return Plan
     */
    public function setLockedAt(\DateTimeInterface $lockedAt = null)
    {
        $this->lockedAt = $lockedAt;

        return $this;
    }

    /**
     * Get lockedAt.
     *
     * @return \DateTime
     */
    public function getLockedAt()
    {
        return $this->lockedAt;
    }

    /**
     * Set lockedBy.
     *
     *
     * @return DocumentTemplate
     */
    public function setLockedBy(\PostparcBundle\Entity\User $lockedBy = null)
    {
        $this->lockedBy = $lockedBy;

        return $this;
    }

    /**
     * Get lockedBy.
     *
     * @return \PostparcBundle\Entity\User
     */
    public function getLockedBy()
    {
        return $this->lockedBy;
    }
}
