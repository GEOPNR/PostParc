<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;

/**
 * PersonnalFieldsRestriction.
 *
 * @ORM\Table(name="personnalFieldsRestriction")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\PersonnalFieldsRestrictionRepository")
 */
class PersonnalFieldsRestriction
{
    use EntityTimestampableTrait;

    public const personnalFields = [
      'profession' => 'Person.field.profession',
      'birthDate' => 'Person.field.birthDate',
      'birthLocation' => 'Person.field.birthLocation',
      'observation' => 'Person.field.observation',
      'nbMinorChildreen' => 'Person.field.nbMinorChildreen',
      'nbMajorChildreen' => 'Person.field.nbMajorChildreen',
      'preferedEmails' => 'Person.field.preferedEmails',
      'tags' => 'genericFields.tags',
    ];

    public const coordinateFields = [
         'addressLine1' => 'Coordinate.field.addressLine1',
         'addressLine2' => 'Coordinate.field.addressLine2',
         'addressLine3' => 'Coordinate.field.addressLine3',
         'cedex' => 'Coordinate.field.cedex',
         'phone' => 'Coordinate.field.phone',
         'mobilePhone' => 'Coordinate.field.mobilePhone',
         'fax' => 'Coordinate.field.fax',
         'webSite' => 'Coordinate.field.webSite',
         'zipCode' => 'City.field.zipCode',
         'city' => 'Coordinate.field.city',
         'email' => 'Coordinate.field.email',
         'organization' => 'Coordinate.field.organization',
         'facebookAccount' => 'Coordinate.field.facebookAccount',
         'twitterAccount' => 'Coordinate.field.twitterAccount',
         'phoneCode' => 'Coordinate.field.phoneCode',
         'geographicalCoordinate' => 'Coordinate.field.geographicalCoordinate',
      ];

    public const roles = [
        'ROLE_USER' => 'User.roles.viewer',
        'ROLE_CONTRIBUTOR' => 'User.roles.contributor',
        'ROLE_CONTRIBUTOR_PLUS' => 'User.roles.contributor_plus',
        'ROLE_ADMIN' => 'User.roles.admin',
      ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var array
     *
     * @ORM\Column(name="restrictions", type="json_array", nullable=true)
     */
    private $restrictions;

    /**
     * @ORM\OneToOne(targetEntity="Entity", inversedBy="personnalFieldsRestriction")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;

    /**
     * @return string
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function getRoles()
    {
        return self::roles;
    }

    public function getCoordinateFields()
    {
        return self::coordinateFields;
    }

    public function getPersonnalFields()
    {
        return self::personnalFields;
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
     * Set restrictions.
     *
     * @param array|null $restrictions
     *
     * @return PersonnalFieldsRestriction
     */
    public function setRestrictions($restrictions = null)
    {
        $this->restrictions = $restrictions;

        return $this;
    }

    /**
     * Get restrictions.
     *
     * @return array|null
     */
    public function getRestrictions()
    {
        return $this->restrictions;
    }

    /**
     * Set entity.
     *
     * @param \PostparcBundle\Entity\Entity|null $entity
     *
     * @return PersonnalFieldsRestriction
     */
    public function setEntity(\PostparcBundle\Entity\Entity $entity = null)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity.
     *
     * @return \PostparcBundle\Entity\Entity|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return PersonnalFieldsRestriction
     */
    public function setCreated(\DateTimeInterface $created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Set updated.
     *
     * @param \DateTime $updated
     *
     * @return PersonnalFieldsRestriction
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;

        return $this;
    }
}
