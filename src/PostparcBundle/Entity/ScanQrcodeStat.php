<?php

namespace PostparcBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;

/**
 * ScanQrcodeStat.
 *
 * @ORM\Table(name="scanQrcodeStat")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\ScanQrcodeStatRepository")
 */
class ScanQrcodeStat
{
    use EntityTimestampableTrait;

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
     * @ORM\Column(name="className", type="string", length=255)
     */
    private $className;

    /**
     * @var int
     *
     * @ORM\Column(name="objectId", type="integer")
     */
    private $objectId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="completeName", type="string", length=255)
     */
    private $completeName;
    
    /**
     * @var int
     *
     * @ORM\Column(name="entityID", type="integer", nullable=true)
     */
    private $entityID;
    
        
    /**
     * Constructor
     */
    public function __construct()
    {

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
     * Set className.
     *
     * @param string $className
     *
     * @return ScanQrcodeStat
     */
    public function setClassName($className)
    {
        $this->className = $className;

        return $this;
    }

    /**
     * Get className.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
    
    /**
     * Set completeName.
     *
     * @param string $completeName
     *
     * @return ScanQrcodeStat
     */
    public function setCompleteName($completeName)
    {
        $this->completeName = $completeName;

        return $this;
    }

    /**
     * Get completeName.
     *
     * @return string
     */
    public function getCompleteName()
    {
        return $this->completeName;
    }

    /**
     * Set objectId.
     *
     * @param int $objectId
     *
     * @return ScanQrcodeStat
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    /**
     * Get objectId.
     *
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }
    
    /**
     * Set env.
     *
     * @param int $entityID
     *
     * @return ScanQrcodeStat
     */
    public function setEntityID($entityID)
    {
        $this->entityID = $entityID;

        return $this;
    }

    /**
     * Get entityID.
     *
     * @return int
     */
    public function getEntityID()
    {
        return $this->entityID;
    }



    
}
