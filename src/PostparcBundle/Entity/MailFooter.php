<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * MailFooter.
 *
 * @ORM\Table(name="mail_footer")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\MailFooterRepository")
 * @Gedmo\Loggable
 */
class MailFooter
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
     * @var string
     *
     * @ORM\Column(name="footer", type="text")
     */
    private $footer;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="mailFooters")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $user;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="mailFootersCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="mailFootersUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\OneToMany(targetEntity="EventAlert", mappedBy="mailFooter", cascade={"persist"})
     */
    protected $eventAlerts;

    /**
     * @return string
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
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
     * @return MailFooter
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
     * Set footer.
     *
     * @param string $footer
     *
     * @return MailFooter
     */
    public function setFooter($footer)
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Get footer.
     *
     * @return string
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * Set user.
     *
     * @param \PostparcBundle\Entity\User|null $user
     *
     * @return MailFooter
     */
    public function setUser(\PostparcBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return \PostparcBundle\Entity\User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->eventAlerts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add eventAlert.
     *
     *
     * @return MailFooter
     */
    public function addEventAlert(\PostparcBundle\Entity\EventAlert $eventAlert)
    {
        $this->eventAlerts[] = $eventAlert;

        return $this;
    }

    /**
     * Remove eventAlert.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventAlert(\PostparcBundle\Entity\EventAlert $eventAlert)
    {
        return $this->eventAlerts->removeElement($eventAlert);
    }

    /**
     * Get eventAlerts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventAlerts()
    {
        return $this->eventAlerts;
    }
}
