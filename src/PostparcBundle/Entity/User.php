<?php

namespace PostparcBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\UserRepository")
 * @ORM\Table(name="fos_user", indexes={@ORM\Index(name="user_slugs", columns={"slug"})})
 * @UniqueEntity("email")
 * @UniqueEntity("username")
 */
class User extends BaseUser
{
    /**
     * @var \PostparcBundle\Entity\Event[]|mixed
     */
    public $eventsOrganizedBy;
    use EntityTimestampableTrait;
    use EntityBlameableTrait;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    protected $username;

    /**
     * @var string
     * @Assert\Email()
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @Gedmo\Slug(fields={"lastName","firstName"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @var array
     *
     * @ORM\Column(name="configs", type="json_array", nullable=true)
     */
    private $configs;

    /**
     * @var array
     *
     * @ORM\Column(name="favorites", type="json_array", nullable=true)
     */
    private $favorites;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="usersCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="usersUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @var bool
     *
     * @ORM\Column(name="wishes_to_be_informed_of_changes", type="boolean", options={"default" = "0"})
     */
    private $wishesToBeInformedOfChanges = false;

    /**
     * @var int
     * @ORM\Column(name="results_per_page", type="integer", options={"default" = "25"})
     */
    protected $resultsPerPage = 25;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="users")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;

    /**
     * @ORM\OneToMany(targetEntity="Service", mappedBy="createdBy", cascade={ "persist"})
     */
    private $servicesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Service", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $servicesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="PersonFunction", mappedBy="createdBy", cascade={ "persist"})
     */
    private $personFunctionsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="PersonFunction", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $personFunctionsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Help", mappedBy="createdBy", cascade={ "persist"})
     */
    private $helpsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Help", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $helpsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="SearchList", mappedBy="createdBy", cascade={ "persist"})
     */
    private $searchListsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="SearchList", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $searchListsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Group", mappedBy="createdBy", cascade={ "persist"})
     */
    private $groupsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Group", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $groupsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Coordinate", mappedBy="createdBy", cascade={ "persist"})
     */
    private $coordinatesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Coordinate", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $coordinatesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Civility", mappedBy="createdBy", cascade={ "persist"})
     */
    private $civilitiesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Civility", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $civilitiesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="City", mappedBy="createdBy", cascade={ "persist"})
     */
    private $citiesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="City", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $citiesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="AdditionalFunction", mappedBy="createdBy", cascade={ "persist"})
     */
    private $additionalFunctionsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="AdditionalFunction", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $additionalFunctionsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="createdBy", cascade={ "persist"})
     */
    private $pfosCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $pfosUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="PrintFormat", mappedBy="createdBy", cascade={ "persist"})
     */
    private $printFormatsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="PrintFormat", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $printFormatsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="OrganizationType", mappedBy="createdBy", cascade={ "persist"})
     */
    private $organizationTypesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="OrganizationType", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $organizationTypesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="TerritoryType", mappedBy="createdBy", cascade={ "persist"})
     */
    private $territoryTypesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="TerritoryType", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $territoryTypesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Territory", mappedBy="createdBy", cascade={ "persist"})
     */
    private $territoriesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Territory", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $territoriesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Profession", mappedBy="createdBy", cascade={ "persist"})
     */
    private $professionsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Profession", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $professionsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="DocumentTemplate", mappedBy="createdBy", cascade={ "persist"})
     */
    private $documentTemplatesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="DocumentTemplate", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $documentTemplatesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="MailFooter", mappedBy="createdBy", cascade={ "persist"})
     */
    private $mailFootersCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="MailFooter", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $mailFootersUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="MailStats", mappedBy="createdBy", cascade={ "persist"})
     */
    private $mailStatsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="createdBy", cascade={ "persist"})
     */
    private $tagsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $tagsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="MandateType", mappedBy="createdBy", cascade={ "persist"})
     */
    private $mandateTypesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="MandateType", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $mandateTypesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="createdBy", cascade={ "persist"})
     */
    private $representationsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $representationsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="createdBy", cascade={ "persist"})
     */
    private $eventsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $eventsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="EventType", mappedBy="createdBy", cascade={ "persist"})
     */
    private $eventTypesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="EventType", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $eventTypesUpdatedBy;

    /**
     * @ORM\ManyToMany(targetEntity="Event", mappedBy="organizators")
     */
    private $eventsOrganizator;

    /**
     * @ORM\OneToMany(targetEntity="EventAlert", mappedBy="createdBy", cascade={ "persist"})
     */
    private $eventAlertsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="EventAlert", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $eventAlertsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="createdBy", cascade={ "persist"})
     */
    private $usersCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $usersUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="ReaderLimitation", mappedBy="createdBy", cascade={ "persist"})
     */
    private $readerLimitationsCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="ReaderLimitation", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $readerLimitationsUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Note", mappedBy="createdBy", cascade={ "persist"})
     */
    private $notesCreatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Note", mappedBy="updatedBy", cascade={ "persist"})
     */
    private $notesUpdatedBy;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="alerter", cascade={ "persist"})
     */
    private $representationAlerters;

    /**
     * @ORM\OneToMany(targetEntity="MailFooter", mappedBy="user", cascade={ "persist"})
     */
    private $mailFooters;

    /**
     * @ORM\ManyToOne(targetEntity="Coordinate", inversedBy="users", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id")
     */
    protected $coordinate;

    public function __construct()
    {
        parent::__construct();
        $this->enabled = true;
        $this->mailFooters = new \Doctrine\Common\Collections\ArrayCollection();
        $this->configs = [
            'show_SharedContents' => true,
        ];
    }

    /**
     * @return type
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * @return type
     */
    public function getDisplayName()
    {
        return $this->firstName . ' ' . $this->lastName . ' (' . parent::getUsername() . ') ';
    }

    /**
     * @return type
     */
    public function __toString()
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    /**
     * Add servicesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Service $servicesCreatedBy
     *
     * @return User
     */
    public function addServicesCreatedBy(\PostparcBundle\Entity\Service $servicesCreatedBy)
    {
        $this->servicesCreatedBy[] = $servicesCreatedBy;

        return $this;
    }

    /**
     * Remove servicesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Service $servicesCreatedBy
     */
    public function removeServicesCreatedBy(\PostparcBundle\Entity\Service $servicesCreatedBy)
    {
        $this->servicesCreatedBy->removeElement($servicesCreatedBy);
    }

    /**
     * Get servicesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getServicesCreatedBy()
    {
        return $this->servicesCreatedBy;
    }

    /**
     * Add servicesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Service $servicesUpdatedBy
     *
     * @return User
     */
    public function addServicesUpdatedBy(\PostparcBundle\Entity\Service $servicesUpdatedBy)
    {
        $this->servicesUpdatedBy[] = $servicesUpdatedBy;

        return $this;
    }

    /**
     * Remove servicesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Service $servicesUpdatedBy
     */
    public function removeServicesUpdatedBy(\PostparcBundle\Entity\Service $servicesUpdatedBy)
    {
        $this->servicesUpdatedBy->removeElement($servicesUpdatedBy);
    }

    /**
     * Get servicesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getServicesUpdatedBy()
    {
        return $this->servicesUpdatedBy;
    }

    /**
     * Add personFunctionsCreatedBy.
     *
     * @param \PostparcBundle\Entity\PersonFunction $personFunctionsCreatedBy
     *
     * @return User
     */
    public function addPersonFunctionsCreatedBy(\PostparcBundle\Entity\PersonFunction $personFunctionsCreatedBy)
    {
        $this->personFunctionsCreatedBy[] = $personFunctionsCreatedBy;

        return $this;
    }

    /**
     * Remove personFunctionsCreatedBy.
     *
     * @param \PostparcBundle\Entity\PersonFunction $personFunctionsCreatedBy
     */
    public function removePersonFunctionsCreatedBy(\PostparcBundle\Entity\PersonFunction $personFunctionsCreatedBy)
    {
        $this->personFunctionsCreatedBy->removeElement($personFunctionsCreatedBy);
    }

    /**
     * Get personFunctionsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonFunctionsCreatedBy()
    {
        return $this->personFunctionsCreatedBy;
    }

    /**
     * Add personFunctionsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\PersonFunction $personFunctionsUpdatedBy
     *
     * @return User
     */
    public function addPersonFunctionsUpdatedBy(\PostparcBundle\Entity\PersonFunction $personFunctionsUpdatedBy)
    {
        $this->personFunctionsUpdatedBy[] = $personFunctionsUpdatedBy;

        return $this;
    }

    /**
     * Remove personFunctionsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\PersonFunction $personFunctionsUpdatedBy
     */
    public function removePersonFunctionsUpdatedBy(\PostparcBundle\Entity\PersonFunction $personFunctionsUpdatedBy)
    {
        $this->personFunctionsUpdatedBy->removeElement($personFunctionsUpdatedBy);
    }

    /**
     * Get personFunctionsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPersonFunctionsUpdatedBy()
    {
        return $this->personFunctionsUpdatedBy;
    }

    /**
     * Add helpsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Help $helpsCreatedBy
     *
     * @return User
     */
    public function addHelpsCreatedBy(\PostparcBundle\Entity\Help $helpsCreatedBy)
    {
        $this->helpsCreatedBy[] = $helpsCreatedBy;

        return $this;
    }

    /**
     * Remove helpsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Help $helpsCreatedBy
     */
    public function removeHelpsCreatedBy(\PostparcBundle\Entity\Help $helpsCreatedBy)
    {
        $this->helpsCreatedBy->removeElement($helpsCreatedBy);
    }

    /**
     * Get helpsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHelpsCreatedBy()
    {
        return $this->helpsCreatedBy;
    }

    /**
     * Add helpsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Help $helpsUpdatedBy
     *
     * @return User
     */
    public function addHelpsUpdatedBy(\PostparcBundle\Entity\Help $helpsUpdatedBy)
    {
        $this->helpsUpdatedBy[] = $helpsUpdatedBy;

        return $this;
    }

    /**
     * Remove helpsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Help $helpsUpdatedBy
     */
    public function removeHelpsUpdatedBy(\PostparcBundle\Entity\Help $helpsUpdatedBy)
    {
        $this->helpsUpdatedBy->removeElement($helpsUpdatedBy);
    }

    /**
     * Get helpsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getHelpsUpdatedBy()
    {
        return $this->helpsUpdatedBy;
    }

    /**
     * Add searchListsCreatedBy.
     *
     * @param \PostparcBundle\Entity\SearchList $searchListsCreatedBy
     *
     * @return User
     */
    public function addSearchListsCreatedBy(\PostparcBundle\Entity\SearchList $searchListsCreatedBy)
    {
        $this->searchListsCreatedBy[] = $searchListsCreatedBy;

        return $this;
    }

    /**
     * Remove searchListsCreatedBy.
     *
     * @param \PostparcBundle\Entity\SearchList $searchListsCreatedBy
     */
    public function removeSearchListsCreatedBy(\PostparcBundle\Entity\SearchList $searchListsCreatedBy)
    {
        $this->searchListsCreatedBy->removeElement($searchListsCreatedBy);
    }

    /**
     * Get searchListsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSearchListsCreatedBy()
    {
        return $this->searchListsCreatedBy;
    }

    /**
     * Add searchListsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\SearchList $searchListsUpdatedBy
     *
     * @return User
     */
    public function addSearchListsUpdatedBy(\PostparcBundle\Entity\SearchList $searchListsUpdatedBy)
    {
        $this->searchListsUpdatedBy[] = $searchListsUpdatedBy;

        return $this;
    }

    /**
     * Remove searchListsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\SearchList $searchListsUpdatedBy
     */
    public function removeSearchListsUpdatedBy(\PostparcBundle\Entity\SearchList $searchListsUpdatedBy)
    {
        $this->searchListsUpdatedBy->removeElement($searchListsUpdatedBy);
    }

    /**
     * Get searchListsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSearchListsUpdatedBy()
    {
        return $this->searchListsUpdatedBy;
    }

    /**
     * Add groupsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Group $groupsCreatedBy
     *
     * @return User
     */
    public function addGroupsCreatedBy(\PostparcBundle\Entity\Group $groupsCreatedBy)
    {
        $this->groupsCreatedBy[] = $groupsCreatedBy;

        return $this;
    }

    /**
     * Remove groupsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Group $groupsCreatedBy
     */
    public function removeGroupsCreatedBy(\PostparcBundle\Entity\Group $groupsCreatedBy)
    {
        $this->groupsCreatedBy->removeElement($groupsCreatedBy);
    }

    /**
     * Get groupsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupsCreatedBy()
    {
        return $this->groupsCreatedBy;
    }

    /**
     * Add groupsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Group $groupsUpdatedBy
     *
     * @return User
     */
    public function addGroupsUpdatedBy(\PostparcBundle\Entity\Group $groupsUpdatedBy)
    {
        $this->groupsUpdatedBy[] = $groupsUpdatedBy;

        return $this;
    }

    /**
     * Remove groupsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Group $groupsUpdatedBy
     */
    public function removeGroupsUpdatedBy(\PostparcBundle\Entity\Group $groupsUpdatedBy)
    {
        $this->groupsUpdatedBy->removeElement($groupsUpdatedBy);
    }

    /**
     * Get groupsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroupsUpdatedBy()
    {
        return $this->groupsUpdatedBy;
    }

    /**
     * Add coordinatesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Coordinate $coordinatesCreatedBy
     *
     * @return User
     */
    public function addCoordinatesCreatedBy(\PostparcBundle\Entity\Coordinate $coordinatesCreatedBy)
    {
        $this->coordinatesCreatedBy[] = $coordinatesCreatedBy;

        return $this;
    }

    /**
     * Remove coordinatesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Coordinate $coordinatesCreatedBy
     */
    public function removeCoordinatesCreatedBy(\PostparcBundle\Entity\Coordinate $coordinatesCreatedBy)
    {
        $this->coordinatesCreatedBy->removeElement($coordinatesCreatedBy);
    }

    /**
     * Get coordinatesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCoordinatesCreatedBy()
    {
        return $this->coordinatesCreatedBy;
    }

    /**
     * Add coordinatesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Coordinate $coordinatesUpdatedBy
     *
     * @return User
     */
    public function addCoordinatesUpdatedBy(\PostparcBundle\Entity\Coordinate $coordinatesUpdatedBy)
    {
        $this->coordinatesUpdatedBy[] = $coordinatesUpdatedBy;

        return $this;
    }

    /**
     * Remove coordinatesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Coordinate $coordinatesUpdatedBy
     */
    public function removeCoordinatesUpdatedBy(\PostparcBundle\Entity\Coordinate $coordinatesUpdatedBy)
    {
        $this->coordinatesUpdatedBy->removeElement($coordinatesUpdatedBy);
    }

    /**
     * Get coordinatesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCoordinatesUpdatedBy()
    {
        return $this->coordinatesUpdatedBy;
    }

    /**
     * Add civilitiesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Civility $civilitiesCreatedBy
     *
     * @return User
     */
    public function addCivilitiesCreatedBy(\PostparcBundle\Entity\Civility $civilitiesCreatedBy)
    {
        $this->civilitiesCreatedBy[] = $civilitiesCreatedBy;

        return $this;
    }

    /**
     * Remove civilitiesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Civility $civilitiesCreatedBy
     */
    public function removeCivilitiesCreatedBy(\PostparcBundle\Entity\Civility $civilitiesCreatedBy)
    {
        $this->civilitiesCreatedBy->removeElement($civilitiesCreatedBy);
    }

    /**
     * Get civilitiesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCivilitiesCreatedBy()
    {
        return $this->civilitiesCreatedBy;
    }

    /**
     * Add civilitiesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Civility $civilitiesUpdatedBy
     *
     * @return User
     */
    public function addCivilitiesUpdatedBy(\PostparcBundle\Entity\Civility $civilitiesUpdatedBy)
    {
        $this->civilitiesUpdatedBy[] = $civilitiesUpdatedBy;

        return $this;
    }

    /**
     * Remove civilitiesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Civility $civilitiesUpdatedBy
     */
    public function removeCivilitiesUpdatedBy(\PostparcBundle\Entity\Civility $civilitiesUpdatedBy)
    {
        $this->civilitiesUpdatedBy->removeElement($civilitiesUpdatedBy);
    }

    /**
     * Get civilitiesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCivilitiesUpdatedBy()
    {
        return $this->civilitiesUpdatedBy;
    }

    /**
     * Add citiesCreatedBy.
     *
     * @param \PostparcBundle\Entity\City $citiesCreatedBy
     *
     * @return User
     */
    public function addCitiesCreatedBy(\PostparcBundle\Entity\City $citiesCreatedBy)
    {
        $this->citiesCreatedBy[] = $citiesCreatedBy;

        return $this;
    }

    /**
     * Remove citiesCreatedBy.
     *
     * @param \PostparcBundle\Entity\City $citiesCreatedBy
     */
    public function removeCitiesCreatedBy(\PostparcBundle\Entity\City $citiesCreatedBy)
    {
        $this->citiesCreatedBy->removeElement($citiesCreatedBy);
    }

    /**
     * Get citiesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCitiesCreatedBy()
    {
        return $this->citiesCreatedBy;
    }

    /**
     * Add citiesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\City $citiesUpdatedBy
     *
     * @return User
     */
    public function addCitiesUpdatedBy(\PostparcBundle\Entity\City $citiesUpdatedBy)
    {
        $this->citiesUpdatedBy[] = $citiesUpdatedBy;

        return $this;
    }

    /**
     * Remove citiesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\City $citiesUpdatedBy
     */
    public function removeCitiesUpdatedBy(\PostparcBundle\Entity\City $citiesUpdatedBy)
    {
        $this->citiesUpdatedBy->removeElement($citiesUpdatedBy);
    }

    /**
     * Get citiesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCitiesUpdatedBy()
    {
        return $this->citiesUpdatedBy;
    }

    /**
     * Add additionalFunctionsCreatedBy.
     *
     * @param \PostparcBundle\Entity\AdditionalFunction $additionalFunctionsCreatedBy
     *
     * @return User
     */
    public function addAdditionalFunctionsCreatedBy(\PostparcBundle\Entity\AdditionalFunction $additionalFunctionsCreatedBy)
    {
        $this->additionalFunctionsCreatedBy[] = $additionalFunctionsCreatedBy;

        return $this;
    }

    /**
     * Remove additionalFunctionsCreatedBy.
     *
     * @param \PostparcBundle\Entity\AdditionalFunction $additionalFunctionsCreatedBy
     */
    public function removeAdditionalFunctionsCreatedBy(\PostparcBundle\Entity\AdditionalFunction $additionalFunctionsCreatedBy)
    {
        $this->additionalFunctionsCreatedBy->removeElement($additionalFunctionsCreatedBy);
    }

    /**
     * Get additionalFunctionsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdditionalFunctionsCreatedBy()
    {
        return $this->additionalFunctionsCreatedBy;
    }

    /**
     * Add additionalFunctionsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\AdditionalFunction $additionalFunctionsUpdatedBy
     *
     * @return User
     */
    public function addAdditionalFunctionsUpdatedBy(\PostparcBundle\Entity\AdditionalFunction $additionalFunctionsUpdatedBy)
    {
        $this->additionalFunctionsUpdatedBy[] = $additionalFunctionsUpdatedBy;

        return $this;
    }

    /**
     * Remove additionalFunctionsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\AdditionalFunction $additionalFunctionsUpdatedBy
     */
    public function removeAdditionalFunctionsUpdatedBy(\PostparcBundle\Entity\AdditionalFunction $additionalFunctionsUpdatedBy)
    {
        $this->additionalFunctionsUpdatedBy->removeElement($additionalFunctionsUpdatedBy);
    }

    /**
     * Get additionalFunctionsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdditionalFunctionsUpdatedBy()
    {
        return $this->additionalFunctionsUpdatedBy;
    }

    /**
     * Add pfosCreatedBy.
     *
     * @param \PostparcBundle\Entity\Pfo $pfosCreatedBy
     *
     * @return User
     */
    public function addPfosCreatedBy(\PostparcBundle\Entity\Pfo $pfosCreatedBy)
    {
        $this->pfosCreatedBy[] = $pfosCreatedBy;

        return $this;
    }

    /**
     * Remove pfosCreatedBy.
     *
     * @param \PostparcBundle\Entity\Pfo $pfosCreatedBy
     */
    public function removePfosCreatedBy(\PostparcBundle\Entity\Pfo $pfosCreatedBy)
    {
        $this->pfosCreatedBy->removeElement($pfosCreatedBy);
    }

    /**
     * Get pfosCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfosCreatedBy()
    {
        return $this->pfosCreatedBy;
    }

    /**
     * Add pfosUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Pfo $pfosUpdatedBy
     *
     * @return User
     */
    public function addPfosUpdatedBy(\PostparcBundle\Entity\Pfo $pfosUpdatedBy)
    {
        $this->pfosUpdatedBy[] = $pfosUpdatedBy;

        return $this;
    }

    /**
     * Remove pfosUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Pfo $pfosUpdatedBy
     */
    public function removePfosUpdatedBy(\PostparcBundle\Entity\Pfo $pfosUpdatedBy)
    {
        $this->pfosUpdatedBy->removeElement($pfosUpdatedBy);
    }

    /**
     * Get pfosUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfosUpdatedBy()
    {
        return $this->pfosUpdatedBy;
    }

    /**
     * Set firstName.
     *
     * @param string $firstName
     *
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName.
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName.
     *
     * @param string $lastName
     *
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName.
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Add printFormatsCreatedBy.
     *
     * @param \PostparcBundle\Entity\PrintFormat $printFormatsCreatedBy
     *
     * @return User
     */
    public function addPrintFormatsCreatedBy(\PostparcBundle\Entity\PrintFormat $printFormatsCreatedBy)
    {
        $this->printFormatsCreatedBy[] = $printFormatsCreatedBy;

        return $this;
    }

    /**
     * Remove printFormatsCreatedBy.
     *
     * @param \PostparcBundle\Entity\PrintFormat $printFormatsCreatedBy
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removePrintFormatsCreatedBy(\PostparcBundle\Entity\PrintFormat $printFormatsCreatedBy)
    {
        return $this->printFormatsCreatedBy->removeElement($printFormatsCreatedBy);
    }

    /**
     * Get printFormatsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrintFormatsCreatedBy()
    {
        return $this->printFormatsCreatedBy;
    }

    /**
     * Add printFormatsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\PrintFormat $printFormatsUpdatedBy
     *
     * @return User
     */
    public function addPrintFormatsUpdatedBy(\PostparcBundle\Entity\PrintFormat $printFormatsUpdatedBy)
    {
        $this->printFormatsUpdatedBy[] = $printFormatsUpdatedBy;

        return $this;
    }

    /**
     * Remove printFormatsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\PrintFormat $printFormatsUpdatedBy
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removePrintFormatsUpdatedBy(\PostparcBundle\Entity\PrintFormat $printFormatsUpdatedBy)
    {
        return $this->printFormatsUpdatedBy->removeElement($printFormatsUpdatedBy);
    }

    /**
     * Get printFormatsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrintFormatsUpdatedBy()
    {
        return $this->printFormatsUpdatedBy;
    }

    /**
     * Add organizationTypesCreatedBy.
     *
     * @param \PostparcBundle\Entity\OrganizationType $organizationTypesCreatedBy
     *
     * @return User
     */
    public function addOrganizationTypesCreatedBy(\PostparcBundle\Entity\OrganizationType $organizationTypesCreatedBy)
    {
        $this->organizationTypesCreatedBy[] = $organizationTypesCreatedBy;

        return $this;
    }

    /**
     * Remove organizationTypesCreatedBy.
     *
     * @param \PostparcBundle\Entity\OrganizationType $organizationTypesCreatedBy
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeOrganizationTypesCreatedBy(\PostparcBundle\Entity\OrganizationType $organizationTypesCreatedBy)
    {
        return $this->organizationTypesCreatedBy->removeElement($organizationTypesCreatedBy);
    }

    /**
     * Get organizationTypesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizationTypesCreatedBy()
    {
        return $this->organizationTypesCreatedBy;
    }

    /**
     * Add organizationTypesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\OrganizationType $organizationTypesUpdatedBy
     *
     * @return User
     */
    public function addOrganizationTypesUpdatedBy(\PostparcBundle\Entity\OrganizationType $organizationTypesUpdatedBy)
    {
        $this->organizationTypesUpdatedBy[] = $organizationTypesUpdatedBy;

        return $this;
    }

    /**
     * Remove organizationTypesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\OrganizationType $organizationTypesUpdatedBy
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeOrganizationTypesUpdatedBy(\PostparcBundle\Entity\OrganizationType $organizationTypesUpdatedBy)
    {
        return $this->organizationTypesUpdatedBy->removeElement($organizationTypesUpdatedBy);
    }

    /**
     * Get organizationTypesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizationTypesUpdatedBy()
    {
        return $this->organizationTypesUpdatedBy;
    }

    /**
     * Set wishesToBeInformedOfChanges.
     *
     * @param bool $wishesToBeInformedOfChanges
     *
     * @return User
     */
    public function setWishesToBeInformedOfChanges($wishesToBeInformedOfChanges)
    {
        $this->wishesToBeInformedOfChanges = $wishesToBeInformedOfChanges;

        return $this;
    }

    /**
     * Get wishesToBeInformedOfChanges.
     *
     * @return bool
     */
    public function getWishesToBeInformedOfChanges()
    {
        return $this->wishesToBeInformedOfChanges;
    }

    /**
     * Set resultsPerPage.
     *
     * @param int $resultsPerPage
     *
     * @return User
     */
    public function setResultsPerPage($resultsPerPage)
    {
        $this->resultsPerPage = $resultsPerPage;

        return $this;
    }

    /**
     * Get resultsPerPage.
     *
     * @return int
     */
    public function getResultsPerPage()
    {
        return $this->resultsPerPage;
    }

    /**
     * Add documentTemplatesCreatedBy.
     *
     * @param \PostparcBundle\Entity\DocumentTemplate $documentTemplatesCreatedBy
     *
     * @return User
     */
    public function addDocumentTemplatesCreatedBy(\PostparcBundle\Entity\DocumentTemplate $documentTemplatesCreatedBy)
    {
        $this->documentTemplatesCreatedBy[] = $documentTemplatesCreatedBy;

        return $this;
    }

    /**
     * Remove documentTemplatesCreatedBy.
     *
     * @param \PostparcBundle\Entity\DocumentTemplate $documentTemplatesCreatedBy
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeDocumentTemplatesCreatedBy(\PostparcBundle\Entity\DocumentTemplate $documentTemplatesCreatedBy)
    {
        return $this->documentTemplatesCreatedBy->removeElement($documentTemplatesCreatedBy);
    }

    /**
     * Get documentTemplatesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocumentTemplatesCreatedBy()
    {
        return $this->documentTemplatesCreatedBy;
    }

    /**
     * Add documentTemplatesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\DocumentTemplate $documentTemplatesUpdatedBy
     *
     * @return User
     */
    public function addDocumentTemplatesUpdatedBy(\PostparcBundle\Entity\DocumentTemplate $documentTemplatesUpdatedBy)
    {
        $this->documentTemplatesUpdatedBy[] = $documentTemplatesUpdatedBy;

        return $this;
    }

    /**
     * Remove documentTemplatesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\DocumentTemplate $documentTemplatesUpdatedBy
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeDocumentTemplatesUpdatedBy(\PostparcBundle\Entity\DocumentTemplate $documentTemplatesUpdatedBy)
    {
        return $this->documentTemplatesUpdatedBy->removeElement($documentTemplatesUpdatedBy);
    }

    /**
     * Get documentTemplatesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getDocumentTemplatesUpdatedBy()
    {
        return $this->documentTemplatesUpdatedBy;
    }

    /**
     * Add mailFootersCreatedBy.
     *
     * @param \PostparcBundle\Entity\MailFooter $mailFootersCreatedBy
     *
     * @return User
     */
    public function addMailFootersCreatedBy(\PostparcBundle\Entity\MailFooter $mailFootersCreatedBy)
    {
        $this->mailFootersCreatedBy[] = $mailFootersCreatedBy;

        return $this;
    }

    /**
     * Remove mailFootersCreatedBy.
     *
     * @param \PostparcBundle\Entity\MailFooter $mailFootersCreatedBy
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeMailFootersCreatedBy(\PostparcBundle\Entity\MailFooter $mailFootersCreatedBy)
    {
        return $this->mailFootersCreatedBy->removeElement($mailFootersCreatedBy);
    }

    /**
     * Get mailFootersCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMailFootersCreatedBy()
    {
        return $this->mailFootersCreatedBy;
    }

    /**
     * Add mailFootersUpdatedBy.
     *
     * @param \PostparcBundle\Entity\MailFooter $mailFootersUpdatedBy
     *
     * @return User
     */
    public function addMailFootersUpdatedBy(\PostparcBundle\Entity\MailFooter $mailFootersUpdatedBy)
    {
        $this->mailFootersUpdatedBy[] = $mailFootersUpdatedBy;

        return $this;
    }

    /**
     * Remove mailFootersUpdatedBy.
     *
     * @param \PostparcBundle\Entity\MailFooter $mailFootersUpdatedBy
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeMailFootersUpdatedBy(\PostparcBundle\Entity\MailFooter $mailFootersUpdatedBy)
    {
        return $this->mailFootersUpdatedBy->removeElement($mailFootersUpdatedBy);
    }

    /**
     * Get mailFootersUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMailFootersUpdatedBy()
    {
        return $this->mailFootersUpdatedBy;
    }

    /**
     * Add mailStatsCreatedBy.
     *
     * @param \PostparcBundle\Entity\MailStats $mailStatsCreatedBy
     *
     * @return User
     */
    public function addMailStatsCreatedBy(\PostparcBundle\Entity\MailStats $mailStatsCreatedBy)
    {
        $this->mailStatsCreatedBy[] = $mailStatsCreatedBy;

        return $this;
    }

    /**
     * Remove mailStatsCreatedBy.
     *
     * @param \PostparcBundle\Entity\MailStats $mailStatsCreatedBy
     *
     * @return bool tRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeMailStatsCreatedBy(\PostparcBundle\Entity\MailStats $mailStatsCreatedBy)
    {
        return $this->mailStatsCreatedBy->removeElement($mailStatsCreatedBy);
    }

    /**
     * Get mailStatsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMailStatsCreatedBy()
    {
        return $this->mailStatsCreatedBy;
    }

    /**
     * Add tagsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Tag $tagsCreatedBy
     *
     * @return User
     */
    public function addTagsCreatedBy(\PostparcBundle\Entity\Tag $tagsCreatedBy)
    {
        $this->tagsCreatedBy[] = $tagsCreatedBy;

        return $this;
    }

    /**
     * Remove tagsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Tag $tagsCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTagsCreatedBy(\PostparcBundle\Entity\Tag $tagsCreatedBy)
    {
        return $this->tagsCreatedBy->removeElement($tagsCreatedBy);
    }

    /**
     * Get tagsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTagsCreatedBy()
    {
        return $this->tagsCreatedBy;
    }

    /**
     * Add tagsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Tag $tagsUpdatedBy
     *
     * @return User
     */
    public function addTagsUpdatedBy(\PostparcBundle\Entity\Tag $tagsUpdatedBy)
    {
        $this->tagsUpdatedBy[] = $tagsUpdatedBy;

        return $this;
    }

    /**
     * Remove tagsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Tag $tagsUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTagsUpdatedBy(\PostparcBundle\Entity\Tag $tagsUpdatedBy)
    {
        return $this->tagsUpdatedBy->removeElement($tagsUpdatedBy);
    }

    /**
     * Get tagsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTagsUpdatedBy()
    {
        return $this->tagsUpdatedBy;
    }

    /**
     * Add mandateTypesCreatedBy.
     *
     * @param \PostparcBundle\Entity\MandateType $mandateTypesCreatedBy
     *
     * @return User
     */
    public function addMandateTypesCreatedBy(\PostparcBundle\Entity\MandateType $mandateTypesCreatedBy)
    {
        $this->mandateTypesCreatedBy[] = $mandateTypesCreatedBy;

        return $this;
    }

    /**
     * Remove mandateTypesCreatedBy.
     *
     * @param \PostparcBundle\Entity\MandateType $mandateTypesCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeMandateTypesCreatedBy(\PostparcBundle\Entity\MandateType $mandateTypesCreatedBy)
    {
        return $this->mandateTypesCreatedBy->removeElement($mandateTypesCreatedBy);
    }

    /**
     * Get mandateTypesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMandateTypesCreatedBy()
    {
        return $this->mandateTypesCreatedBy;
    }

    /**
     * Add mandateTypesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\MandateType $mandateTypesUpdatedBy
     *
     * @return User
     */
    public function addMandateTypesUpdatedBy(\PostparcBundle\Entity\MandateType $mandateTypesUpdatedBy)
    {
        $this->mandateTypesUpdatedBy[] = $mandateTypesUpdatedBy;

        return $this;
    }

    /**
     * Remove mandateTypesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\MandateType $mandateTypesUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeMandateTypesUpdatedBy(\PostparcBundle\Entity\MandateType $mandateTypesUpdatedBy)
    {
        return $this->mandateTypesUpdatedBy->removeElement($mandateTypesUpdatedBy);
    }

    /**
     * Get mandateTypesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMandateTypesUpdatedBy()
    {
        return $this->mandateTypesUpdatedBy;
    }

    /**
     * Add representationsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Representation $representationsCreatedBy
     *
     * @return User
     */
    public function addRepresentationsCreatedBy(\PostparcBundle\Entity\Representation $representationsCreatedBy)
    {
        $this->representationsCreatedBy[] = $representationsCreatedBy;

        return $this;
    }

    /**
     * Remove representationsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Representation $representationsCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeRepresentationsCreatedBy(\PostparcBundle\Entity\Representation $representationsCreatedBy)
    {
        return $this->representationsCreatedBy->removeElement($representationsCreatedBy);
    }

    /**
     * Get representationsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRepresentationsCreatedBy()
    {
        return $this->representationsCreatedBy;
    }

    /**
     * Add representationsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Representation $representationsUpdatedBy
     *
     * @return User
     */
    public function addRepresentationsUpdatedBy(\PostparcBundle\Entity\Representation $representationsUpdatedBy)
    {
        $this->representationsUpdatedBy[] = $representationsUpdatedBy;

        return $this;
    }

    /**
     * Remove representationsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Representation $representationsUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeRepresentationsUpdatedBy(\PostparcBundle\Entity\Representation $representationsUpdatedBy)
    {
        return $this->representationsUpdatedBy->removeElement($representationsUpdatedBy);
    }

    /**
     * Get representationsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRepresentationsUpdatedBy()
    {
        return $this->representationsUpdatedBy;
    }

    /**
     * Add eventsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Event $eventsCreatedBy
     *
     * @return User
     */
    public function addEventsCreatedBy(\PostparcBundle\Entity\Event $eventsCreatedBy)
    {
        $this->eventsCreatedBy[] = $eventsCreatedBy;

        return $this;
    }

    /**
     * Remove eventsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Event $eventsCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventsCreatedBy(\PostparcBundle\Entity\Event $eventsCreatedBy)
    {
        return $this->eventsCreatedBy->removeElement($eventsCreatedBy);
    }

    /**
     * Get eventsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventsCreatedBy()
    {
        return $this->eventsCreatedBy;
    }

    /**
     * Add eventsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Event $eventsUpdatedBy
     *
     * @return User
     */
    public function addEventsUpdatedBy(\PostparcBundle\Entity\Event $eventsUpdatedBy)
    {
        $this->eventsUpdatedBy[] = $eventsUpdatedBy;

        return $this;
    }

    /**
     * Remove eventsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Event $eventsUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventsUpdatedBy(\PostparcBundle\Entity\Event $eventsUpdatedBy)
    {
        return $this->eventsUpdatedBy->removeElement($eventsUpdatedBy);
    }

    /**
     * Get eventsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventsUpdatedBy()
    {
        return $this->eventsUpdatedBy;
    }

    /**
     * Add eventTypesCreatedBy.
     *
     * @param \PostparcBundle\Entity\EventType $eventTypesCreatedBy
     *
     * @return User
     */
    public function addEventTypesCreatedBy(\PostparcBundle\Entity\EventType $eventTypesCreatedBy)
    {
        $this->eventTypesCreatedBy[] = $eventTypesCreatedBy;

        return $this;
    }

    /**
     * Remove eventTypesCreatedBy.
     *
     * @param \PostparcBundle\Entity\EventType $eventTypesCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventTypesCreatedBy(\PostparcBundle\Entity\EventType $eventTypesCreatedBy)
    {
        return $this->eventTypesCreatedBy->removeElement($eventTypesCreatedBy);
    }

    /**
     * Get eventTypesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventTypesCreatedBy()
    {
        return $this->eventTypesCreatedBy;
    }

    /**
     * Add eventTypesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\EventType $eventTypesUpdatedBy
     *
     * @return User
     */
    public function addEventTypesUpdatedBy(\PostparcBundle\Entity\EventType $eventTypesUpdatedBy)
    {
        $this->eventTypesUpdatedBy[] = $eventTypesUpdatedBy;

        return $this;
    }

    /**
     * Remove eventTypesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\EventType $eventTypesUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventTypesUpdatedBy(\PostparcBundle\Entity\EventType $eventTypesUpdatedBy)
    {
        return $this->eventTypesUpdatedBy->removeElement($eventTypesUpdatedBy);
    }

    /**
     * Get eventTypesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventTypesUpdatedBy()
    {
        return $this->eventTypesUpdatedBy;
    }

    /**
     * Add eventsOrganizedBy.
     *
     * @param \PostparcBundle\Entity\Event $eventsOrganizedBy
     *
     * @return User
     */
    public function addEventsOrganizedBy(\PostparcBundle\Entity\Event $eventsOrganizedBy)
    {
        $this->eventsOrganizedBy[] = $eventsOrganizedBy;

        return $this;
    }

    /**
     * Remove eventsOrganizedBy.
     *
     * @param \PostparcBundle\Entity\Event $eventsOrganizedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventsOrganizedBy(\PostparcBundle\Entity\Event $eventsOrganizedBy)
    {
        return $this->eventsOrganizedBy->removeElement($eventsOrganizedBy);
    }

    /**
     * Get eventsOrganizedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventsOrganizedBy()
    {
        return $this->eventsOrganizedBy;
    }

    /**
     * Add eventAlertsCreatedBy.
     *
     * @param \PostparcBundle\Entity\EventAlert $eventAlertsCreatedBy
     *
     * @return User
     */
    public function addEventAlertsCreatedBy(\PostparcBundle\Entity\EventAlert $eventAlertsCreatedBy)
    {
        $this->eventAlertsCreatedBy[] = $eventAlertsCreatedBy;

        return $this;
    }

    /**
     * Remove eventAlertsCreatedBy.
     *
     * @param \PostparcBundle\Entity\EventAlert $eventAlertsCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventAlertsCreatedBy(\PostparcBundle\Entity\EventAlert $eventAlertsCreatedBy)
    {
        return $this->eventAlertsCreatedBy->removeElement($eventAlertsCreatedBy);
    }

    /**
     * Get eventAlertsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventAlertsCreatedBy()
    {
        return $this->eventAlertsCreatedBy;
    }

    /**
     * Add eventAlertsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\EventAlert $eventAlertsUpdatedBy
     *
     * @return User
     */
    public function addEventAlertsUpdatedBy(\PostparcBundle\Entity\EventAlert $eventAlertsUpdatedBy)
    {
        $this->eventAlertsUpdatedBy[] = $eventAlertsUpdatedBy;

        return $this;
    }

    /**
     * Remove eventAlertsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\EventAlert $eventAlertsUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventAlertsUpdatedBy(\PostparcBundle\Entity\EventAlert $eventAlertsUpdatedBy)
    {
        return $this->eventAlertsUpdatedBy->removeElement($eventAlertsUpdatedBy);
    }

    /**
     * Get eventAlertsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventAlertsUpdatedBy()
    {
        return $this->eventAlertsUpdatedBy;
    }

    /**
     * Add representationAlerter.
     *
     * @param \PostparcBundle\Entity\Representation $representationAlerter
     *
     * @return User
     */
    public function addRepresentationAlerter(\PostparcBundle\Entity\Representation $representationAlerter)
    {
        $this->representationAlerters[] = $representationAlerter;

        return $this;
    }

    /**
     * Remove representationAlerter.
     *
     * @param \PostparcBundle\Entity\Representation $representationAlerter
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeRepresentationAlerter(\PostparcBundle\Entity\Representation $representationAlerter)
    {
        return $this->representationAlerters->removeElement($representationAlerter);
    }

    /**
     * Get representationAlerters.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRepresentationAlerters()
    {
        return $this->representationAlerters;
    }

    /**
     * Set entity.
     *
     * @param \PostparcBundle\Entity\Entity|null $entity
     *
     * @return User
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
     * Add mailFooter.
     *
     * @param \PostparcBundle\Entity\MailFooter $mailFooter
     *
     * @return User
     */
    public function addMailFooter(\PostparcBundle\Entity\MailFooter $mailFooter)
    {
        $this->mailFooters[] = $mailFooter;

        return $this;
    }

    /**
     * Remove mailFooter.
     *
     * @param \PostparcBundle\Entity\MailFooter $mailFooter
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeMailFooter(\PostparcBundle\Entity\MailFooter $mailFooter)
    {
        return $this->mailFooters->removeElement($mailFooter);
    }

    /**
     * Get mailFooters.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMailFooters()
    {
        return $this->mailFooters;
    }

    /**
     * Add usersCreatedBy.
     *
     * @param \PostparcBundle\Entity\User $usersCreatedBy
     *
     * @return User
     */
    public function addUsersCreatedBy(\PostparcBundle\Entity\User $usersCreatedBy)
    {
        $this->usersCreatedBy[] = $usersCreatedBy;

        return $this;
    }

    /**
     * Remove usersCreatedBy.
     *
     * @param \PostparcBundle\Entity\User $usersCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeUsersCreatedBy(\PostparcBundle\Entity\User $usersCreatedBy)
    {
        return $this->usersCreatedBy->removeElement($usersCreatedBy);
    }

    /**
     * Get usersCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsersCreatedBy()
    {
        return $this->usersCreatedBy;
    }

    /**
     * Add usersUpdatedBy.
     *
     * @param \PostparcBundle\Entity\User $usersUpdatedBy
     *
     * @return User
     */
    public function addUsersUpdatedBy(\PostparcBundle\Entity\User $usersUpdatedBy)
    {
        $this->usersUpdatedBy[] = $usersUpdatedBy;

        return $this;
    }

    /**
     * Remove usersUpdatedBy.
     *
     * @param \PostparcBundle\Entity\User $usersUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeUsersUpdatedBy(\PostparcBundle\Entity\User $usersUpdatedBy)
    {
        return $this->usersUpdatedBy->removeElement($usersUpdatedBy);
    }

    /**
     * Get usersUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsersUpdatedBy()
    {
        return $this->usersUpdatedBy;
    }

    /**
     * Add readerLimitationsCreatedBy.
     *
     * @param \PostparcBundle\Entity\ReaderLimitation $readerLimitationsCreatedBy
     *
     * @return User
     */
    public function addReaderLimitationsCreatedBy(\PostparcBundle\Entity\ReaderLimitation $readerLimitationsCreatedBy)
    {
        $this->readerLimitationsCreatedBy[] = $readerLimitationsCreatedBy;

        return $this;
    }

    /**
     * Remove readerLimitationsCreatedBy.
     *
     * @param \PostparcBundle\Entity\ReaderLimitation $readerLimitationsCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeReaderLimitationsCreatedBy(\PostparcBundle\Entity\ReaderLimitation $readerLimitationsCreatedBy)
    {
        return $this->readerLimitationsCreatedBy->removeElement($readerLimitationsCreatedBy);
    }

    /**
     * Get readerLimitationsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReaderLimitationsCreatedBy()
    {
        return $this->readerLimitationsCreatedBy;
    }

    /**
     * Add readerLimitationsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\ReaderLimitation $readerLimitationsUpdatedBy
     *
     * @return User
     */
    public function addReaderLimitationsUpdatedBy(\PostparcBundle\Entity\ReaderLimitation $readerLimitationsUpdatedBy)
    {
        $this->readerLimitationsUpdatedBy[] = $readerLimitationsUpdatedBy;

        return $this;
    }

    /**
     * Remove readerLimitationsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\ReaderLimitation $readerLimitationsUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeReaderLimitationsUpdatedBy(\PostparcBundle\Entity\ReaderLimitation $readerLimitationsUpdatedBy)
    {
        return $this->readerLimitationsUpdatedBy->removeElement($readerLimitationsUpdatedBy);
    }

    /**
     * Get readerLimitationsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReaderLimitationsUpdatedBy()
    {
        return $this->readerLimitationsUpdatedBy;
    }

    /**
     * Set slug.
     *
     * @param string $slug
     *
     * @return User
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Add eventsOrganizator.
     *
     * @param \PostparcBundle\Entity\Event $eventsOrganizator
     *
     * @return User
     */
    public function addEventsOrganizator(\PostparcBundle\Entity\Event $eventsOrganizator)
    {
        $this->eventsOrganizator[] = $eventsOrganizator;

        return $this;
    }

    /**
     * Remove eventsOrganizator.
     *
     * @param \PostparcBundle\Entity\Event $eventsOrganizator
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEventsOrganizator(\PostparcBundle\Entity\Event $eventsOrganizator)
    {
        return $this->eventsOrganizator->removeElement($eventsOrganizator);
    }

    /**
     * Get eventsOrganizator.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEventsOrganizator()
    {
        return $this->eventsOrganizator;
    }

    /**
     * Set configs.
     *
     * @param array|null $configs
     *
     * @return User
     */
    public function setConfigs($configs = null)
    {
        $this->configs = $configs;

        return $this;
    }

    /**
     * Get configs.
     *
     * @return array|null
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * Set favorites.
     *
     * @param array|null $configs
     *
     * @return User
     */
    public function setFavorites($favorites = null)
    {
        $this->favorites = $favorites;

        return $this;
    }

    /**
     * Get favorites.
     *
     * @return array|null
     */
    public function getFavorites()
    {
        return $this->favorites;
    }

    /**
     * Set coordinate.
     *
     * @param \PostparcBundle\Entity\Coordinate $coordinate
     *
     * @return User
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

    /**
     * Set created.
     *
     * @param \DateTime $created
     *
     * @return User
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
     * @return User
     */
    public function setUpdated(\DateTimeInterface $updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Add territoryTypesCreatedBy.
     *
     * @param \PostparcBundle\Entity\TerritoryType $territoryTypesCreatedBy
     *
     * @return User
     */
    public function addTerritoryTypesCreatedBy(\PostparcBundle\Entity\TerritoryType $territoryTypesCreatedBy)
    {
        $this->territoryTypesCreatedBy[] = $territoryTypesCreatedBy;

        return $this;
    }

    /**
     * Remove territoryTypesCreatedBy.
     *
     * @param \PostparcBundle\Entity\TerritoryType $territoryTypesCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTerritoryTypesCreatedBy(\PostparcBundle\Entity\TerritoryType $territoryTypesCreatedBy)
    {
        return $this->territoryTypesCreatedBy->removeElement($territoryTypesCreatedBy);
    }

    /**
     * Get territoryTypesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoryTypesCreatedBy()
    {
        return $this->territoryTypesCreatedBy;
    }

    /**
     * Add territoryTypesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\TerritoryType $territoryTypesUpdatedBy
     *
     * @return User
     */
    public function addTerritoryTypesUpdatedBy(\PostparcBundle\Entity\TerritoryType $territoryTypesUpdatedBy)
    {
        $this->territoryTypesUpdatedBy[] = $territoryTypesUpdatedBy;

        return $this;
    }

    /**
     * Remove territoryTypesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\TerritoryType $territoryTypesUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTerritoryTypesUpdatedBy(\PostparcBundle\Entity\TerritoryType $territoryTypesUpdatedBy)
    {
        return $this->territoryTypesUpdatedBy->removeElement($territoryTypesUpdatedBy);
    }

    /**
     * Get territoryTypesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoryTypesUpdatedBy()
    {
        return $this->territoryTypesUpdatedBy;
    }

    /**
     * Add territoriesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Territory $territoriesCreatedBy
     *
     * @return User
     */
    public function addTerritoriesCreatedBy(\PostparcBundle\Entity\Territory $territoriesCreatedBy)
    {
        $this->territoriesCreatedBy[] = $territoriesCreatedBy;

        return $this;
    }

    /**
     * Remove territoriesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Territory $territoriesCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTerritoriesCreatedBy(\PostparcBundle\Entity\Territory $territoriesCreatedBy)
    {
        return $this->territoriesCreatedBy->removeElement($territoriesCreatedBy);
    }

    /**
     * Get territoriesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoriesCreatedBy()
    {
        return $this->territoriesCreatedBy;
    }

    /**
     * Add territoriesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Territory $territoriesUpdatedBy
     *
     * @return User
     */
    public function addTerritoriesUpdatedBy(\PostparcBundle\Entity\Territory $territoriesUpdatedBy)
    {
        $this->territoriesUpdatedBy[] = $territoriesUpdatedBy;

        return $this;
    }

    /**
     * Remove territoriesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Territory $territoriesUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTerritoriesUpdatedBy(\PostparcBundle\Entity\Territory $territoriesUpdatedBy)
    {
        return $this->territoriesUpdatedBy->removeElement($territoriesUpdatedBy);
    }

    /**
     * Get territoriesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTerritoriesUpdatedBy()
    {
        return $this->territoriesUpdatedBy;
    }

    /**
     * Add professionsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Profession $professionsCreatedBy
     *
     * @return User
     */
    public function addProfessionsCreatedBy(\PostparcBundle\Entity\Profession $professionsCreatedBy)
    {
        $this->professionsCreatedBy[] = $professionsCreatedBy;

        return $this;
    }

    /**
     * Remove professionsCreatedBy.
     *
     * @param \PostparcBundle\Entity\Profession $professionsCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeProfessionsCreatedBy(\PostparcBundle\Entity\Profession $professionsCreatedBy)
    {
        return $this->professionsCreatedBy->removeElement($professionsCreatedBy);
    }

    /**
     * Get professionsCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProfessionsCreatedBy()
    {
        return $this->professionsCreatedBy;
    }

    /**
     * Add professionsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Profession $professionsUpdatedBy
     *
     * @return User
     */
    public function addProfessionsUpdatedBy(\PostparcBundle\Entity\Profession $professionsUpdatedBy)
    {
        $this->professionsUpdatedBy[] = $professionsUpdatedBy;

        return $this;
    }

    /**
     * Remove professionsUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Profession $professionsUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeProfessionsUpdatedBy(\PostparcBundle\Entity\Profession $professionsUpdatedBy)
    {
        return $this->professionsUpdatedBy->removeElement($professionsUpdatedBy);
    }

    /**
     * Get professionsUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProfessionsUpdatedBy()
    {
        return $this->professionsUpdatedBy;
    }

    /**
     * Add notesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Note $notesCreatedBy
     *
     * @return User
     */
    public function addNotesCreatedBy(\PostparcBundle\Entity\Note $notesCreatedBy)
    {
        $this->notesCreatedBy[] = $notesCreatedBy;

        return $this;
    }

    /**
     * Remove notesCreatedBy.
     *
     * @param \PostparcBundle\Entity\Note $notesCreatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeNotesCreatedBy(\PostparcBundle\Entity\Note $notesCreatedBy)
    {
        return $this->notesCreatedBy->removeElement($notesCreatedBy);
    }

    /**
     * Get notesCreatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotesCreatedBy()
    {
        return $this->notesCreatedBy;
    }

    /**
     * Add notesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Note $notesUpdatedBy
     *
     * @return User
     */
    public function addNotesUpdatedBy(\PostparcBundle\Entity\Note $notesUpdatedBy)
    {
        $this->notesUpdatedBy[] = $notesUpdatedBy;

        return $this;
    }

    /**
     * Remove notesUpdatedBy.
     *
     * @param \PostparcBundle\Entity\Note $notesUpdatedBy
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeNotesUpdatedBy(\PostparcBundle\Entity\Note $notesUpdatedBy)
    {
        return $this->notesUpdatedBy->removeElement($notesUpdatedBy);
    }

    /**
     * Get notesUpdatedBy.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotesUpdatedBy()
    {
        return $this->notesUpdatedBy;
    }
}
