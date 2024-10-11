<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\EmailRepository")
 * @ORM\Table(name="email")
 *
 * @Gedmo\Loggable
 */
class Email
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
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=500, nullable=true)
     * @Gedmo\Versioned
     *
     * Assert\Regex(
     *      pattern="/([\w+-.%]+@[\w-.]+\.[A-Za-z]{2,4},?)+/",
     *      match=false,
     *      message="format invalide"
     * )
     *
     * Assert\Email()
     */
    private $email;

    /**
     * @ORM\OneToMany(targetEntity="Coordinate", mappedBy="email", cascade={"persist"})
     */
    private $coordinateEmails;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="email", cascade={"remove", "persist"})
     */
    private $pfoEmails;

    /**
     * @ORM\ManyToMany(targetEntity="Pfo", mappedBy="preferedEmails")
     */
    private $pfosPreferedEmails;

    /**
     * @ORM\ManyToMany(targetEntity="Person", mappedBy="preferedEmails")
     */
    private $personsPreferedEmails;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="preferedEmail", cascade={"persist"})
     */
    private $representationsPreferedEmail;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->coordinateEmails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pfoEmails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->pfosPreferedEmails = new \Doctrine\Common\Collections\ArrayCollection();
        $this->personsPreferedEmails = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return '' . str_replace(' ', '', $this->email);
    }

    public function getApiFormated()
    {
        return [
            'id' => $this->id,
            'email' => str_replace(' ', '', $this->email),
        ];
    }

    /**
     * @return string
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
     * Set email.
     *
     * @param string $email
     *
     * @return Email
     */
    public function setEmail($email)
    {
        $this->email = str_replace(' ', '', $email);

        return $this;
    }

    /**
     * Get email.
     *
     * @return string
     */
    public function getEmail()
    {
        return str_replace(' ', '', $this->email);
    }

    /**
     * Add coordinateEmail.
     *
     *
     * @return Email
     */
    public function addCoordinateEmail(\PostparcBundle\Entity\Coordinate $coordinateEmail)
    {
        $this->coordinateEmails[] = $coordinateEmail;

        return $this;
    }

    /**
     * Remove coordinateEmail.
     */
    public function removeCoordinateEmail(\PostparcBundle\Entity\Coordinate $coordinateEmail)
    {
        $this->coordinateEmails->removeElement($coordinateEmail);
    }

    /**
     * Get coordinateEmails.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCoordinateEmails()
    {
        return $this->coordinateEmails;
    }

    /**
     * Add pfoEmail.
     *
     *
     * @return Email
     */
    public function addPfoEmail(\PostparcBundle\Entity\Pfo $pfoEmail)
    {
        $this->pfoEmails[] = $pfoEmail;

        return $this;
    }

    /**
     * Remove pfoEmail.
     */
    public function removePfoEmail(\PostparcBundle\Entity\Pfo $pfoEmail)
    {
        $this->pfoEmails->removeElement($pfoEmail);
    }

    /**
     * Get pfoEmails.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfoEmails()
    {
        return $this->pfoEmails;
    }

    /**
     * Add pfosPreferedEmail.
     *
     *
     * @return Email
     */
    public function addPfosPreferedEmail(\PostparcBundle\Entity\Pfo $pfosPreferedEmail)
    {
        $this->pfosPreferedEmails[] = $pfosPreferedEmail;

        return $this;
    }

    /**
     * Remove pfosPreferedEmail.
     */
    public function removePfosPreferedEmail(\PostparcBundle\Entity\Pfo $pfosPreferedEmail)
    {
        $this->pfosPreferedEmails->removeElement($pfosPreferedEmail);
    }

    /**
     * Get pfosPreferedEmails.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfosPreferedEmails()
    {
        return $this->pfosPreferedEmails;
    }

    /**
     * Add personsPreferedEmail.
     *
     *
     * @return Email
     */
    public function addPersonsPreferedEmail(\PostparcBundle\Entity\Person $personsPreferedEmail)
    {
        $this->personsPreferedEmails[] = $personsPreferedEmail;

        return $this;
    }

    /**
     * Remove personsPreferedEmail.
     */
    public function removePersonsPreferedEmail(\PostparcBundle\Entity\Person $personsPreferedEmail)
    {
        $this->personsPreferedEmails->removeElement($personsPreferedEmail);
    }

    /**
     * Get personsPreferedEmails.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonsPreferedEmails()
    {
        return $this->personsPreferedEmails;
    }

    /**
     * Add representationsPreferedEmail.
     *
     *
     * @return Email
     */
    public function addRepresentationsPreferedEmail(\PostparcBundle\Entity\Representation $representationsPreferedEmail)
    {
        $this->representationsPreferedEmail[] = $representationsPreferedEmail;

        return $this;
    }

    /**
     * Remove representationsPreferedEmail.
     */
    public function removeRepresentationsPreferedEmail(\PostparcBundle\Entity\Representation $representationsPreferedEmail)
    {
        $this->representationsPreferedEmail->removeElement($representationsPreferedEmail);
    }

    /**
     * Get representationsPreferedEmail.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRepresentationsPreferedEmail()
    {
        return $this->representationsPreferedEmail;
    }
}
