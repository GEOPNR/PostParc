<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use PostparcBundle\Entity\Traits\EntityTimestampableTrait;
use PostparcBundle\Entity\Traits\EntityBlameableTrait;
use PostparcBundle\Entity\Traits\EntitySoftDeletableTrait;

/**
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\PrintFormatRepository")
 * @ORM\Table(name="print_format")
 * @Gedmo\Loggable
 */
class PrintFormat
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
     */
    private $id;

    /**
     * @Gedmo\Slug(fields={"name"})
     * @ORM\Column(length=128, unique=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     * @Gedmo\Versioned
     */
    private $name;

    /**
     * @var text
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @Gedmo\Versioned
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="format", type="string", length=12, nullable=true)
     * @Gedmo\Versioned
     */
    private $format;

    /**
     * @var string
     *
     * @ORM\Column(name="orientation", type="string", length=12, nullable=true)
     * @Gedmo\Versioned
     */
    private $orientation;

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
     * @var int
     *
     * @ORM\Column(name="number_per_row", type="integer")
     * @Gedmo\Versioned
     */
    private $numberPerRow;

    /**
     * @var float
     *
     * @ORM\Column(name="sticker_height", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $stickerHeight;

    /**
     * @var float
     *
     * @ORM\Column(name="sticker_width", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $stickerWidth;

    /**
     * @var float
     *
     * @ORM\Column(name="padding_horizontal_inter_sticker", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $paddingHorizontalInterSticker;

    /**
     * @var float
     *
     * @ORM\Column(name="padding_vertical_inter_sticker", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $paddingVerticalInterSticker;

    /**
     * @var float
     *
     * @ORM\Column(name="margin_horizontal_inter_sticker", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $marginHorizontalInterSticker;

    /**
     * @var float
     *
     * @ORM\Column(name="margin_vertical_inter_sticker", type="float", nullable=true)
     * @Gedmo\Versioned
     */
    private $marginVerticalInterSticker;

    /**
     * @var string
     *
     * @ORM\Column(name="sticker_fonts", type="string", length=50, nullable=true, options={"default" = "helvetica"})
     * @Gedmo\Versioned
     */
    private $stickerFonts = 'helvetica';

    /**
     * @var int
     *
     * @ORM\Column(name="sticker_fontsize", type="integer", options={"default" = "10"}, nullable=false)
     * @Gedmo\Versioned
     */
    private $stickerFontsize = 10;

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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="printFormatsCreatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $createdBy;

    /**
     * @Gedmo\Blameable(on="update")
     * @ORM\ManyToOne(targetEntity="User", inversedBy="printFormatsUpdatedBy")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $updatedBy;

    /**
     * @ORM\ManyToOne(targetEntity="Entity", inversedBy="printFormats")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    protected $entity;

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
     * Set slug.
     *
     * @param string $slug
     *
     * @return PrintFormat
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
     * Set name.
     *
     * @param string $name
     *
     * @return PrintFormat
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
     * Set description.
     *
     * @param string $description
     *
     * @return PrintFormat
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
     * Set format.
     *
     * @param string $format
     *
     * @return PrintFormat
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get format.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set orientation.
     *
     * @param string $orientation
     *
     * @return PrintFormat
     */
    public function setOrientation($orientation)
    {
        $this->orientation = $orientation;

        return $this;
    }

    /**
     * Get orientation.
     *
     * @return string
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * Set marginTop.
     *
     * @param float $marginTop
     *
     * @return PrintFormat
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
     * @return PrintFormat
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
     * @return PrintFormat
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
     * @return PrintFormat
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
     * Set numberPerRow.
     *
     * @param int $numberPerRow
     *
     * @return PrintFormat
     */
    public function setNumberPerRow($numberPerRow)
    {
        $this->numberPerRow = $numberPerRow;

        return $this;
    }

    /**
     * Get numberPerRow.
     *
     * @return int
     */
    public function getNumberPerRow()
    {
        return $this->numberPerRow;
    }

    /**
     * Set stickerHeight.
     *
     * @param float $stickerHeight
     *
     * @return PrintFormat
     */
    public function setStickerHeight($stickerHeight)
    {
        $this->stickerHeight = $stickerHeight;

        return $this;
    }

    /**
     * Get stickerHeight.
     *
     * @return float
     */
    public function getStickerHeight()
    {
        return $this->stickerHeight;
    }

    /**
     * Set stickerWidth.
     *
     * @param float $stickerWidth
     *
     * @return PrintFormat
     */
    public function setStickerWidth($stickerWidth)
    {
        $this->stickerWidth = $stickerWidth;

        return $this;
    }

    /**
     * Get stickerWidth.
     *
     * @return float
     */
    public function getStickerWidth()
    {
        return $this->stickerWidth;
    }

    /**
     * Set paddingHorizontalInterSticker.
     *
     * @param float $paddingHorizontalInterSticker
     *
     * @return PrintFormat
     */
    public function setPaddingHorizontalInterSticker($paddingHorizontalInterSticker)
    {
        $this->paddingHorizontalInterSticker = $paddingHorizontalInterSticker;

        return $this;
    }

    /**
     * Get paddingHorizontalInterSticker.
     *
     * @return float
     */
    public function getPaddingHorizontalInterSticker()
    {
        return $this->paddingHorizontalInterSticker;
    }

    /**
     * Set paddingVerticalInterSticker.
     *
     * @param float $paddingVerticalInterSticker
     *
     * @return PrintFormat
     */
    public function setPaddingVerticalInterSticker($paddingVerticalInterSticker)
    {
        $this->paddingVerticalInterSticker = $paddingVerticalInterSticker;

        return $this;
    }

    /**
     * Get paddingVerticalInterSticker.
     *
     * @return float
     */
    public function getPaddingVerticalInterSticker()
    {
        return $this->paddingVerticalInterSticker;
    }

    /**
     * Set marginHorizontalInterSticker.
     *
     * @param float $marginHorizontalInterSticker
     *
     * @return PrintFormat
     */
    public function setMarginHorizontalInterSticker($marginHorizontalInterSticker)
    {
        $this->marginHorizontalInterSticker = $marginHorizontalInterSticker;

        return $this;
    }

    /**
     * Get marginHorizontalInterSticker.
     *
     * @return float
     */
    public function getMarginHorizontalInterSticker()
    {
        return $this->marginHorizontalInterSticker;
    }

    /**
     * Set marginVerticalInterSticker.
     *
     * @param float $marginVerticalInterSticker
     *
     * @return PrintFormat
     */
    public function setMarginVerticalInterSticker($marginVerticalInterSticker)
    {
        $this->marginVerticalInterSticker = $marginVerticalInterSticker;

        return $this;
    }

    /**
     * Get marginVerticalInterSticker.
     *
     * @return float
     */
    public function getMarginVerticalInterSticker()
    {
        return $this->marginVerticalInterSticker;
    }

    /**
     * Set isShared.
     *
     * @param bool $isShared
     *
     * @return PrintFormat
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
     * @return PrintFormat
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
     * Set stickerFonts.
     *
     * @param string|null $stickerFonts
     *
     * @return PrintFormat
     */
    public function setStickerFonts($stickerFonts = null)
    {
        $this->stickerFonts = $stickerFonts;

        return $this;
    }

    /**
     * Get stickerFonts.
     *
     * @return string|null
     */
    public function getStickerFonts()
    {
        return $this->stickerFonts;
    }

    /**
     * Set stickerFontsize.
     *
     * @param int|null $stickerFontsize
     *
     * @return PrintFormat
     */
    public function setStickerFontsize($stickerFontsize = null)
    {
        $this->stickerFontsize = $stickerFontsize;

        return $this;
    }

    /**
     * Get stickerFontsize.
     *
     * @return int|null
     */
    public function getStickerFontsize()
    {
        return $this->stickerFontsize;
    }

    /**
     * Set isEditableByOtherEntities.
     *
     * @param bool $isEditableByOtherEntities
     *
     * @return PrintFormat
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
