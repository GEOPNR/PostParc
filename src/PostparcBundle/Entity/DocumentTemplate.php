<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;
use PostparcBundle\Entity\Traits\EntitySoftDeletableTrait;

/**
 * DocumentTemplate.
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\DocumentTemplateRepository")
 * @Gedmo\Loggable
 * @ORM\HasLifecycleCallbacks
 */
class DocumentTemplate
{
    use EntityTimestampableTrait;
    use EntityBlameableTrait;
    use EntitySoftDeletableTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var bool
     *
     * @ORM\Column(name="isActive", type="boolean")
     * @Gedmo\Versioned
     */
    private $isActive = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="mailable", type="boolean")
     */
    private $mailable;

    /**
     * @var string
     *
     * @ORM\Column(name="subject", type="string", length=500, nullable=true)
     * @Gedmo\Versioned
     */
    private $subject;

    /**
     * @var string
     *
     * @ORM\Column(name="env", type="string", length=50)
     */
    private $env;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text")
     * @Gedmo\Versioned
     */
    private $body;

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
     * @var datetime
     *
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $deletedBy;

    /**
     * @Gedmo\Blameable(on="create")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="documentTemplatesCreatedBy", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="documentTemplatesUpdatedBy", cascade={"persist"})
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @var float
     *
     * @ORM\Column(name="margin_top", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $marginTop;

    /**
     * @var float
     *
     * @ORM\Column(name="margin_bottom", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $marginBottom;

    /**
     * @var float
     *
     * @ORM\Column(name="margin_left", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $marginLeft;

    /**
     * @var float
     *
     * @ORM\Column(name="margin_right", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $marginRight;

    /**
     * @var bool
     *
     * @ORM\Column(name="print_footer", type="boolean")
     */
    private $printFooter;

    /**
     * @var bool
     *
     * @ORM\Column(name="print_image", type="boolean")
     */
    private $printImage;

    /**
     * @var bool
     *
     * @ORM\Column(name="print_image_as_background", type="boolean")
     */
    private $printImageAsBackground;

    /**
     * @var string
     *
     * @ORM\Column(name="footer", type="string", length=500, nullable=true)
     * @Gedmo\Versioned
     */
    private $footer;

    /**
     * @var string
     * @Assert\File( maxSize = "1024k", mimeTypes={"image/png"}, mimeTypesMessage = "restrictionPngFormat")
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @ORM\OneToOne(targetEntity="Attachment")
     * @ORM\JoinColumn(name="attachment_id", referencedColumnName="id")
     */
    private $attachment;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="documentTemplates")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="isPrivate", type="boolean")
     */
    private $isPrivate;

    /**
     * @return string
     */
    public function __toString()
    {
        return strlen($this->name) !== 0 ? $this->name : ' ';
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function __construct()
    {
        $this->isShared = false;
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
     * @return DocumentTemplate
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
     * Set isActive.
     *
     * @param bool $isActive
     *
     * @return DocumentTemplate
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive.
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set subject.
     *
     * @param string $subject
     *
     * @return DocumentTemplate
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject.
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set env.
     *
     * @param string $env
     *
     * @return DocumentTemplate
     */
    public function setEnv($env)
    {
        $this->env = $env;

        return $this;
    }

    /**
     * Get env.
     *
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * Set body.
     *
     * @param string $body
     *
     * @return DocumentTemplate
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return DocumentTemplate
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set mailable.
     *
     * @param bool $mailable
     *
     * @return DocumentTemplate
     */
    public function setMailable($mailable)
    {
        $this->mailable = $mailable;

        return $this;
    }

    /**
     * Get mailable.
     *
     * @return bool
     */
    public function getMailable()
    {
        return $this->mailable;
    }

    /**
     * Set marginTop.
     *
     * @param float $marginTop
     *
     * @return DocumentTemplate
     */
    public function setMarginTop($marginTop)
    {
        $this->marginTop = $marginTop;

        return $this;
    }

    /**
     * Get marginTop.
     *
     * @return float
     */
    public function getMarginTop()
    {
        return $this->marginTop;
    }

    /**
     * Set marginBottom.
     *
     * @param float $marginBottom
     *
     * @return DocumentTemplate
     */
    public function setMarginBottom($marginBottom)
    {
        $this->marginBottom = $marginBottom;

        return $this;
    }

    /**
     * Get marginBottom.
     *
     * @return float
     */
    public function getMarginBottom()
    {
        return $this->marginBottom;
    }

    /**
     * Set marginLeft.
     *
     * @param float $marginLeft
     *
     * @return DocumentTemplate
     */
    public function setMarginLeft($marginLeft)
    {
        $this->marginLeft = $marginLeft;

        return $this;
    }

    /**
     * Get marginLeft.
     *
     * @return float
     */
    public function getMarginLeft()
    {
        return $this->marginLeft;
    }

    /**
     * Set marginRight.
     *
     * @param float $marginRight
     *
     * @return DocumentTemplate
     */
    public function setMarginRight($marginRight)
    {
        $this->marginRight = $marginRight;

        return $this;
    }

    /**
     * Get marginRight.
     *
     * @return float
     */
    public function getMarginRight()
    {
        return $this->marginRight;
    }

    /**
     * Set printFooter.
     *
     * @param bool $printFooter
     *
     * @return DocumentTemplate
     */
    public function setPrintFooter($printFooter)
    {
        $this->printFooter = $printFooter;

        return $this;
    }

    /**
     * Get printFooter.
     *
     * @return bool
     */
    public function getPrintFooter()
    {
        return $this->printFooter;
    }

    /**
     * Set printImage.
     *
     * @param bool $printImage
     *
     * @return DocumentTemplate
     */
    public function setPrintImage($printImage)
    {
        $this->printImage = $printImage;

        return $this;
    }

    /**
     * Get printImage.
     *
     * @return bool
     */
    public function getPrintImage()
    {
        return $this->printImage;
    }

    /**
     * Set printImageAsBackground.
     *
     * @param bool $printImageAsBackground
     *
     * @return DocumentTemplate
     */
    public function setPrintImageAsBackground($printImageAsBackground)
    {
        $this->printImageAsBackground = $printImageAsBackground;

        return $this;
    }

    /**
     * Get printImageAsBackground.
     *
     * @return bool
     */
    public function getPrintImageAsBackground()
    {
        return $this->printImageAsBackground;
    }

    /**
     * Set footer.
     *
     * @param string $footer
     *
     * @return DocumentTemplate
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
     * Set image.
     *
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * Get image.
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }
    
     /**
     * Set isPrivate.
     *
     * @param bool $isPrivate
     *
     * @return DocumentTemplate
     */
    public function setIsPrivate($isPrivate)
    {
        $this->isPrivate = $isPrivate;

        return $this;
    }

    /**
     * Get isPrivate.
     *
     * @return bool
     */
    public function getIsPrivate()
    {
        return $this->isPrivate;
    }

    /*
     * *******************   SPECIALS METHODS FOR UPLOAD FILE *********************
     */

    /**
     * @return string
     */
    public function getFullImagePath()
    {
        return null === $this->image ? null : $this->getUploadRootDir() . $this->image;
    }

    /**
     * @return string
     */
    public function getwebPath()
    {
        return null === $this->image ? null : 'uploads/documentTemplateImages/' . $this->env . '/' . $this->id . '/' . $this->image;
    }

    /**
     * @return string
     */
    protected function getUploadRootDir()
    {
        // the absolute directory path where uploaded documents should be saved
        return $this->getTmpUploadRootDir() . $this->getId() . '/';
    }

    /**
     * @return string
     */
    protected function getTmpUploadRootDir()
    {
        $documentTemplateImagesFolder = __DIR__ . '/../../../web/uploads/documentTemplateImages';
        if (!is_dir($documentTemplateImagesFolder)) {
            mkdir($documentTemplateImagesFolder);
        }
        // the absolute directory path where uploaded documents should be saved
        $dir = $documentTemplateImagesFolder . '/' . $this->env;
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        return $dir . '/';
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function uploadImage()
    {
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
    public function moveImage()
    {
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
    public function removeImage()
    {
        if (file_exists($this->getFullImagePath())) {
            unlink($this->getFullImagePath());
            rmdir($this->getUploadRootDir());
        }
    }

    /**
     * Set attachment.
     *
     *
     * @return DocumentTemplate
     */
    public function setAttachment(\PostparcBundle\Entity\Attachment $attachment = null)
    {
        $this->attachment = $attachment;

        return $this;
    }

    /**
     * Get attachment.
     *
     * @return \PostparcBundle\Entity\Attachment
     */
    public function getAttachment()
    {
        return $this->attachment;
    }

    /**
     * Set isShared.
     *
     * @param bool $isShared
     *
     * @return DocumentTemplate
     */
    public function setIsShared($isShared)
    {
        $this->isShared = $isShared;

        return $this;
    }

    /**
     * Get isShared.
     *
     * @return bool
     */
    public function getIsShared()
    {
        return $this->isShared;
    }

    /**
     * Set entity.
     *
     * @param \PostparcBundle\Entity\Entity|null $entity
     *
     * @return DocumentTemplate
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
     * Set isEditableByOtherEntities.
     *
     * @param bool $isEditableByOtherEntities
     *
     * @return DocumentTemplate
     */
    public function setIsEditableByOtherEntities($isEditableByOtherEntities)
    {
        $this->isEditableByOtherEntities = $isEditableByOtherEntities;

        return $this;
    }

    /**
     * Get isEditableByOtherEntities.
     *
     * @return bool
     */
    public function getIsEditableByOtherEntities()
    {
        return $this->isEditableByOtherEntities;
    }
}
