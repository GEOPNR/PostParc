<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * EntityCoordinateDistance.
 *
 * @ORM\Table(name="entity_coordinate_distance")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\EntityCoordinateDistanceRepository")
 */
class EntityCoordinateDistance
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
     * @ORM\ManyToOne(targetEntity="Entity")
     * @ORM\JoinColumn(name="entity_id", referencedColumnName="id", onDelete="CASCADE")
     *
     **/
    private $entity;

    /**
     * @ORM\ManyToOne(targetEntity="Coordinate")
     * @ORM\JoinColumn(name="coordinate_id", referencedColumnName="id", onDelete="CASCADE")
     *
     **/
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
     * @return EntityCoordinateDistance
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
     * @return EntityCoordinateDistance
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
     * @return EntityCoordinateDistance
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
     * @return EntityCoordinateDistance
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
     * Set entity.
     *
     *
     * @return EntityCoordinateDistance
     */
    public function setEntity(\PostparcBundle\Entity\Entity $entity = null)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity.
     *
     * @return \PostparcBundle\Entity\Entity
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set coordinate.
     *
     *
     * @return EntityCoordinateDistance
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
