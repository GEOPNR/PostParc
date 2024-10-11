<?php

namespace PostparcBundle\Entity\Traits;

trait EntityBlameableTrait
{
    /**
     * Set createdBy.
     *
     *
     * @return City
     */
    public function setCreatedBy(\PostparcBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy.
     *
     * @return \PostparcBundle\Entity\User
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set updatedBy.
     *
     *
     * @return City
     */
    public function setUpdatedBy(\PostparcBundle\Entity\User $updatedBy = null)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy.
     *
     * @return \PostparcBundle\Entity\User
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
