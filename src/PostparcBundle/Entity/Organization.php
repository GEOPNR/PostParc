<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use JeroenDesloovere\VCard\VCard;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityNameTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;
use PostparcBundle\Entity\Traits\EntitySoftDeletableTrait;
use PostparcBundle\Entity\Traits\EntityLockableTrait;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\OrganizationRepository")
 * @ORM\Table(name="organization", indexes={@ORM\Index(name="organization_slugs", columns={"slug"})})
 * @Gedmo\Loggable
 * @ORM\HasLifecycleCallbacks
 */
class Organization {

    use EntityTimestampableTrait;
    use EntityNameTrait;
    use EntityBlameableTrait;
    use EntitySoftDeletableTrait;
    use EntityLockableTrait;

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
     * @Gedmo\Versioned
     * @ORM\Column(name="abbreviation", type="string", length=255, nullable=true)
     */
    private $abbreviation;

    /**
     * @var text
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var int
     *
     * @ORM\Column(name="nbAdherent", type="integer", nullable=true)
     * @Gedmo\Versioned
     */
    private $nbAdherent;

    /**
     * @var string
     * @Assert\File( maxSize = "1024k", mimeTypes={"image/png","image/jpeg"}, mimeTypesMessage = "restrictionPngJpegFormat")
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var text
     *
     * @ORM\Column(name="observation", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $observation;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_shared", type="boolean", options={"default" = "0"})
     * @Gedmo\Versioned
     */
    protected $isShared;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_editable_by_other_entities", type="boolean", options={"default" = "0"})
     * @Gedmo\Versioned
     */
    protected $isEditableByOtherEntities = false;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="additionalFunctionsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="additionalFunctionsUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @var string
     *
     * @ORM\Column(name="env", type="string", length=50)
     */
    private $env;
    
    /**
     * @var string
     * @Gedmo\Versioned
     * @ORM\Column(name="siret", type="string", length=255, nullable=true)
     */
    private $siret;

    /**
     * @ORM\OneToOne(targetEntity="Coordinate", inversedBy="organization",  cascade={"persist","remove"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $coordinate;

    /**
     * @ORM\OneToMany(targetEntity="Pfo", mappedBy="organization", cascade={ "persist"})
     */
    private $pfos;

    /**
     * @ORM\OneToMany(targetEntity="Representation", mappedBy="organization", cascade={"persist"})
     */
    private $representations;

    /**
     * @Gedmo\Versioned
     * @ORM\ManyToOne(targetEntity="OrganizationType", inversedBy="organizations")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organizationType;

    /**
     * @ORM\ManyToMany(targetEntity="Attachment", cascade={"remove", "persist"})
     * @ORM\JoinTable(name="organization_attachments")
     */
    private $attachments;

    /**
     * @ORM\ManyToMany(targetEntity="Tag" , inversedBy="organizations")
     * @ORM\JoinTable(name="organization_tags")
     */
    private $tags;

    /**
     * @ORM\OneToMany(targetEntity="OrganizationLink", mappedBy="organizationOrigin")
     */
    private $organizationOriginLinks;

    /**
     * @ORM\OneToMany(targetEntity="OrganizationLink", mappedBy="organizationLinked")
     */
    private $organizationLinkedLinks;

    /**
     * @ORM\ManyToMany(targetEntity="Event", mappedBy="organizations")
     */
    private $events;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="organizations")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;

    /**
     * @var bool
     * @ORM\Column(name="show_observation", type="boolean", options={"default" = "0"})
     */
    protected $showObservation = false;

    /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="organizations")
     * @ORM\JoinTable(name="organization_groups")
     */
    protected $groups;

    /**
     * @return string
     */
    public function getClassName() {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Constructor.
     */
    public function __construct() {
        $this->pfos = new \Doctrine\Common\Collections\ArrayCollection();
        $this->attachments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->tags = new \Doctrine\Common\Collections\ArrayCollection();
        $this->events = new \Doctrine\Common\Collections\ArrayCollection();
        $this->isShared = false;
    }

    public function getApiFormated($format = 'object') {
        $formated = [
            'id' => $this->id,
            'name' => $this->name,
            'abbreviation' => $this->abbreviation,
            'siret' => $this->siret,
            'slug' => $this->slug,
            'coordinate' => $this->coordinate ? $this->coordinate->getApiFormated($format) : null,
            'organizationType' => $this->getOrganizationType() ? ['id'=> $this->getOrganizationType()->getId(), 'name' => $this->getOrganizationType()->getName()] : null,
        ];
        
        $tags = [];
        foreach ($this->getTags() as $tag) {
            if($tag){
                $tags[] = [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                    'slug' => $tag->getSlug()
                ];
            }
        }
        $formated['tags'] = $tags;

        $groups = [];
        foreach ($this->getGroups() as $group) {
            if($group){
                $groups[] = [
                    'id' => $group->getId(),
                    'name' => $group->getName(),
                    'slug' => $group->getSlug()
                ];
            }
        }
        $formated['groups'] = $groups;

        if ('object' == $format) {
            $formated = array_merge(
                    $formated,
                    [
                        'coordinate' => $this->coordinate->getApiFormated(),
                        'organizationType' => $this->getOrganizationType() ? $this->getOrganizationType()->getName() : null,
                    ]
            );
            $pfos = [];
            foreach ($this->getPfos() as $pfo) {
                $pfos[] = $pfo->getApiFormated('list');
            }
            $formated['pfos'] = $pfos;
        }

        return $formated;
    }

    public function getCity() {
        $city = null;
        if ($this->getCoordinate()) {
            $city = $this->getCoordinate()->getCity();
        }
        return $city;
    }

    public function getEmail() {
        $email = null;
        if ($this->getCoordinate()) {
            $email = $this->getCoordinate()->getemail();
        }
        return $email;
    }

    public function getEmailsArray() {
        $emails = [];

        if ($this->getEmail()) {
            $emails[] = $this->getEmail()->__toString();
        }

        return $emails;
    }

    /**
     * @param Request $postRequest
     * @param bool    $fromPfo
     *
     * @return type
     */
    public function getPrintForSticker($postRequest, $user, $fromPfo = 0, $personnalFieldsRestriction = []) {
        $content = '';
        $separator = '';

        if ($postRequest->has('organization')) {
            $tabFields = $postRequest->get('organization');
//            dump($tabFields);die;
            $printName = false;
            
            if (isset($tabFields['abbreviation']) && $this->getAbbreviation()) {
                // cas particulier organisme avec intitulé mais sans abreviation
                if (!strlen(trim($this->getAbbreviation())) && strlen(trim($this->getName()))) {
                    if (!$printName) {
                        $content .= $separator . trim($this->getName());
                    }
                } else {
                    $content .= $separator . trim($this->getAbbreviation());
                }
                $separator = ' ';
            }
            elseif (isset($tabFields['name'])) {
                // cas particulier organisme sans intitulé mais avec abreviation
                if (!strlen(trim($this->getName())) && strlen(trim($this->getAbbreviation()))) {
                    $content .= $separator . trim($this->getAbbreviation());
                } else {
                    $content .= $separator . trim($this->getName());
                    $printName = true;
                }
                $separator = ' ';
            }

            if (isset($tabFields['coordinate']) && !$fromPfo) {
                $coordinate = $this->getCoordinate();
                if ($coordinate) {
                    $content .= $coordinate->getPrintForSticker($postRequest, $user, $fromPfo, $personnalFieldsRestriction);
                }
            }
        }

        return nl2br($content);
    }

    public function getCoordinateStringForDuplicateSearch() {
        $coord = $this->__toString();

        $coordinate = $this->getCoordinate();
        if ($coordinate) {
            $coord .= $coordinate->__toString();
        }

        return $coord;
    }

    /**
     * generateVcardContent.
     *
     * @param Person $person
     *
     * @return type
     */
    public function generateVcardContent() {
        $vcard = new VCard();
        $vcard->addCompany($this->__toString());

        // add work data
        $coordinate = $this->getCoordinate();

        if ($coordinate) {
            $vcard->addAddress(null, null, $coordinate->getAddressLine1() . ' ' . $coordinate->getAddressLine2(), $coordinate->getCity(), null, $coordinate->getCity() ? $coordinate->getCity()->getZipCode() : '', $coordinate->getCity() ? $coordinate->getCity()->getCountry() : '', 'WORK');
            $vcard->addEmail($coordinate->getEmail(), 'WORK');
            $vcard->addPhoneNumber($coordinate->getPhone(), 'WORK');
            $vcard->addPhoneNumber($coordinate->getMobilePhone(), 'CELL');
            $vcard->addPhoneNumber($coordinate->getFax(), 'FAX');
            $vcard->addURL($coordinate->getWebSite(), 'WORK');
        }
        
        // image
        if($this->getImage()){
            $url = __DIR__ . '/../../../web/'.$this->getwebPath();
            $vcard->addPhoto($url);
        }
        
        // tags
        $tags = [];
        foreach($this->getTags() as $tag) {
            $tags[] = $tag->__toString();
        }
        if(count($tags)) {
            $vcard->addCategories($tags);
        }
        
        // add postparc note
        $vcard->addNote('vcard generated by postparc');
        
        // response
        return $vcard->getOutput();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return Service
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * Set abbreviation.
     *
     * @param string $abbreviation
     *
     * @return Organization
     */
    public function setAbbreviation($abbreviation) {
        $this->abbreviation = $abbreviation;

        return $this;
    }

    /**
     * Get abbreviation.
     *
     * @return string
     */
    public function getAbbreviation() {
        return $this->abbreviation;
    }
    
    /**
     * Set siret.
     *
     * @param string $siret
     *
     * @return Organization
     */
    public function setSiret($siret) {
        $this->siret = $siret;

        return $this;
    }

    /**
     * Get siret.
     *
     * @return string
     */
    public function getSiret() {
        return $this->siret;
    }

    /**
     * Set env.
     *
     * @param string $env
     *
     * @return DocumentTemplate
     */
    public function setEnv($env) {
        $this->env = $env;

        return $this;
    }

    /**
     * Get env.
     *
     * @return string
     */
    public function getEnv() {
        return $this->env;
    }

    /**
     * Set coordinate.
     *
     *
     * @return Organization
     */
    public function setCoordinate(\PostparcBundle\Entity\Coordinate $coordinate = null) {
        $this->coordinate = $coordinate;

        return $this;
    }

    /**
     * Get coordinate.
     *
     * @return \PostparcBundle\Entity\Coordinate
     */
    public function getCoordinate() {
        return $this->coordinate;
    }

    /**
     * Add pfo.
     *
     *
     * @return Organization
     */
    public function addPfo(\PostparcBundle\Entity\Pfo $pfo) {
        $this->pfos[] = $pfo;

        return $this;
    }

    /**
     * Remove pfo.
     */
    public function removePfo(\PostparcBundle\Entity\Pfo $pfo) {
        $this->pfos->removeElement($pfo);
    }

    /**
     * Get pfos.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPfos() {
        return $this->pfos;
    }

    /**
     * Set organizationType.
     *
     *
     * @return Organization
     */
    public function setOrganizationType(\PostparcBundle\Entity\OrganizationType $organizationType = null) {
        $this->organizationType = $organizationType;

        return $this;
    }

    /**
     * Get organizationType.
     *
     * @return \PostparcBundle\Entity\OrganizationType
     */
    public function getOrganizationType() {
        return $this->organizationType;
    }

    /**
     * Set observation.
     *
     * @param string $observation
     *
     * @return Organization
     */
    public function setObservation($observation) {
        $this->observation = $observation;

        return $this;
    }

    /**
     * Get observation.
     *
     * @return string
     */
    public function getObservation() {
        return $this->observation;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return Organization
     */
    public function setDescription($description = null) {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set nbAdherent.
     *
     * @param int|null $nbAdherent
     *
     * @return Organization
     */
    public function setNbAdherent($nbAdherent = null) {
        $this->nbAdherent = $nbAdherent;

        return $this;
    }

    /**
     * Get nbAdherent.
     *
     * @return int|null
     */
    public function getNbAdherent() {
        return $this->nbAdherent;
    }

    /**
     * Add attachment.
     *
     *
     * @return Organization
     */
    public function addAttachment(\PostparcBundle\Entity\Attachment $attachment) {
        //$this->attachments[] = $attachment;
        //$attachment->addOrganisation($this);
        //$this->attachments->add($attachment);
        return $this;
    }

    /**
     * Remove attachment.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeAttachment(\PostparcBundle\Entity\Attachment $attachment) {
        return $this->attachments->removeElement($attachment);
    }

    /**
     * Get attachments.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAttachments() {
        return $this->attachments;
    }

    /**
     * Add tag.
     *
     *
     * @return Organization
     */
    public function addTag(\PostparcBundle\Entity\Tag $tag) {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * Remove tag.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeTag(\PostparcBundle\Entity\Tag $tag) {
        return $this->tags->removeElement($tag);
    }

    /**
     * Get tags.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTags() {
        return $this->tags;
    }

    /**
     * Set image.
     *
     * @param string|null $image
     *
     * @return Organization
     */
    public function setImage($image = null) {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image.
     *
     * @return string|null
     */
    public function getImage() {
        return $this->image;
    }

    /*
     * *******************   SPECIALS METHODS FOR UPLOAD FILE *********************
     */

    /**
     * @return string
     */
    public function getFullImagePath() {
        return null === $this->image ? null : $this->getUploadRootDir() . $this->image;
    }

    /**
     * @return string
     */
    public function getwebPath() {
        return null === $this->image ? null : 'uploads/organizationImages/' . $this->env . '/' . $this->id . '/' . $this->image;
    }

    /**
     * @return string
     */
    protected function getUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir() . $this->getId() . '/';
    }

    /**
     * @return string
     */
    protected function getTmpUploadRootDir() {
        // the absolute directory path where uploaded documents should be saved
        $dir = __DIR__ . '/../../../web/uploads/organizationImages/' . $this->env;
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        return $dir . '/';
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadImage() {
        // the file property can be empty if the field is not required
        if (null === $this->image) {
            return;
        }
        if ($this->id === 0) {
            $this->image->move($this->getTmpUploadRootDir(), $this->image->getClientOriginalName());
        } else {
            // cas particulier
            if (is_string($this->image)) {
                return;
            }
            $this->image->move($this->getUploadRootDir(), $this->image->getClientOriginalName());
        }
        $this->setImage($this->image->getClientOriginalName());
    }

    /**
     * @ORM\PostPersist()
     */
    public function moveImage() {
        if (null === $this->image) {
            return;
        }
        if (!is_dir($this->getUploadRootDir())) {
            mkdir($this->getUploadRootDir());
        }
        copy($this->getTmpUploadRootDir() . $this->image, $this->getFullImagePath());
        unlink($this->getTmpUploadRootDir() . $this->image);
    }

    /**
     * @ORM\PreRemove()
     */
    public function removeImage() {
        if (file_exists($this->getFullImagePath())) {
            unlink($this->getFullImagePath());
            rmdir($this->getUploadRootDir());
        }
    }

    /**
     * Add organizationOriginLink.
     *
     *
     * @return Organization
     */
    public function addOrganizationOriginLink(\PostparcBundle\Entity\OrganizationLink $organizationOriginLink) {
        $this->organizationOriginLinks[] = $organizationOriginLink;

        return $this;
    }

    /**
     * Remove organizationOriginLink.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeOrganizationOriginLink(\PostparcBundle\Entity\OrganizationLink $organizationOriginLink) {
        return $this->organizationOriginLinks->removeElement($organizationOriginLink);
    }

    /**
     * Get organizationOriginLinks.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizationOriginLinks() {
        return $this->organizationOriginLinks;
    }

    /**
     * Add organizationLinkedLink.
     *
     *
     * @return Organization
     */
    public function addOrganizationLinkedLink(\PostparcBundle\Entity\OrganizationLink $organizationLinkedLink) {
        $this->organizationLinkedLinks[] = $organizationLinkedLink;

        return $this;
    }

    /**
     * Remove organizationLinkedLink.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeOrganizationLinkedLink(\PostparcBundle\Entity\OrganizationLink $organizationLinkedLink) {
        return $this->organizationLinkedLinks->removeElement($organizationLinkedLink);
    }

    /**
     * Get organizationLinkedLinks.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizationLinkedLinks() {
        return $this->organizationLinkedLinks;
    }

    /**
     * Get events.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEvents() {
        return $this->events;
    }

    /**
     * Add event.
     *
     *
     * @return Organization
     */
    public function addEvent(\PostparcBundle\Entity\Event $event) {
        $this->events[] = $event;

        return $this;
    }

    /**
     * Remove event.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeEvent(\PostparcBundle\Entity\Event $event) {
        return $this->events->removeElement($event);
    }

    /**
     * Add representation.
     *
     *
     * @return Organization
     */
    public function addRepresentation(\PostparcBundle\Entity\Representation $representation) {
        $this->representations[] = $representation;

        return $this;
    }

    /**
     * Remove representation.
     *
     *
     * @return bool TRUE if this collection contained the specified element, FALSE otherwise
     */
    public function removeRepresentation(\PostparcBundle\Entity\Representation $representation) {
        return $this->representations->removeElement($representation);
    }

    /**
     * Get representations.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRepresentations() {
        return $this->representations;
    }

    /**
     * Set isShared.
     *
     * @param bool $isShared
     *
     * @return Organization
     */
    public function setIsShared($isShared) {
        $this->isShared = $isShared;

        return $this;
    }

    /**
     * Get isShared.
     *
     * @return bool
     */
    public function getIsShared() {
        return $this->isShared;
    }

    /**
     * Set entity.
     *
     * @param \PostparcBundle\Entity\Entity|null $entity
     *
     * @return Organization
     */
    public function setEntity(\PostparcBundle\Entity\Entity $entity = null) {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Get entity.
     *
     * @return \PostparcBundle\Entity\Entity|null
     */
    public function getEntity() {
        return $this->entity;
    }

    /**
     * Set isEditableByOtherEntities.
     *
     * @param bool $isEditableByOtherEntities
     *
     * @return Organization
     */
    public function setIsEditableByOtherEntities($isEditableByOtherEntities) {
        $this->isEditableByOtherEntities = $isEditableByOtherEntities;

        return $this;
    }

    /**
     * Get isEditableByOtherEntities.
     *
     * @return bool
     */
    public function getIsEditableByOtherEntities() {
        return $this->isEditableByOtherEntities;
    }

    /**
     * Set showObservation.
     *
     * @param bool $showObservation
     *
     * @return Organization
     */
    public function setShowObservation($showObservation) {
        $this->showObservation = $showObservation;

        return $this;
    }

    /**
     * Get showObservation.
     *
     * @return bool
     */
    public function getShowObservation() {
        return $this->showObservation;
    }

    /**
     * Add group.
     *
     *
     * @return Organization
     */
    public function addGroup(\PostparcBundle\Entity\Group $group) {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Remove group.
     */
    public function removeGroup(\PostparcBundle\Entity\Group $group) {
        $this->groups->removeElement($group);
    }

    /**
     * Get groups.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups() {
        return $this->groups;
    }

}
