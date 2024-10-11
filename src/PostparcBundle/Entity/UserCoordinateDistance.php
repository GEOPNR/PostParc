<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserCoordinateDistance.
 *
 * @ORM\Table(name="user_coordinate_distance")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\UserCoordinateDistanceRepository")
 */
class UserCoordinateDistance
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
     * @var int
     *
     * @ORM\Column(name="distanceValue", type="integer")
     */
    private $distanceValue;

    /**
     * @var string
     *
     * @ORM\Column(name="distanceText", type="string", length=255)
     */
    private $distanceText;

    /**
     * @var int
     *
     * @ORM\Column(name="durationValue", type="integer")
     */
    private $durationValue;

    /**
     * @var string
     *
     * @ORM\Column(name="durationText", type="string", length=255)
     */
    private $durationText;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Coordinate")
     * @ORM\JoinColumn(name="coordinate_id", referencedColumnName="id", onDelete="CASCADE")
     *
     * */
    private $coordinate;

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
     * Set distanceValue.
     *
     * @param int $distanceValue
     *
     * @return UserCoordinateDistance
     */
    public function setDistanceValue($distanceValue)
    {
        $this->distanceValue = $distanceValue;

        return $this;
    }

    /**
     * Get distanceValue.
     *
     * @return int
     */
    public function getDistanceValue()
    {
        return $this->distanceValue;
    }

    /**
     * Set distanceText.
     *
     * @param string $distanceText
     *
     * @return UserCoordinateDistance
     */
    public function setDistanceText($distanceText)
    {
        $this->distanceText = $distanceText;

        return $this;
    }

    /**
     * Get distanceText.
     *
     * @return string
     */
    public function getDistanceText()
    {
        return $this->distanceText;
    }

    /**
     * Set durationValue.
     *
     * @param int $durationValue
     *
     * @return UserCoordinateDistance
     */
    public function setDurationValue($durationValue)
    {
        $this->durationValue = $durationValue;

        return $this;
    }

    /**
     * Get durationValue.
     *
     * @return int
     */
    public function getDurationValue()
    {
        return $this->durationValue;
    }

    /**
     * Set durationText.
     *
     * @param string $durationText
     *
     * @return UserCoordinateDistance
     */
    public function setDurationText($durationText)
    {
        $this->durationText = $durationText;

        return $this;
    }

    /**
     * Get durationText.
     *
     * @return string
     */
    public function getDurationText()
    {
        return $this->durationText;
    }

    /**
     * Set user.
     *
     *
     * @return UserCoordinateDistance
     */
    public function setUser(\PostparcBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \PostparcBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set coordinate.
     *
     *
     * @return UserCoordinateDistance
     */
    public function setCoordinate(\PostparcBundle\Entity\Coordinate $coordinate = null)
    {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * Get coordinate.
     *
     * @return \PostparcBundle\Entity\Coordinate
     */
    public function getCoordinate()
    {
        return $this->coordinate;
    }
}
