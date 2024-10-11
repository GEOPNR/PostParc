<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Description of Attachment.
 *
 * @author philg
 *
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\AttachmentRepository")
 * @Vich\Uploadable
 */
class Attachment
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="document_attachment", fileNameProperty="attachmentName", size="attachmentSize")
     *
     * @var File
     */
    private $attachmentFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $attachmentName;

    /**
     * @ORM\Column(type="integer")
     *
     * @var int|null
     */
    private $attachmentSize;

    /**
     * @ORM\Column(type="datetime")
     *
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->updatedAt = new \DateTime('now');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->attachmentName;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $attachment
     *
     * @return Attachment
     */
    public function setAttachmentFile(File $attachment = null)
    {
        $this->attachmentFile = $attachment;

        if ($attachment !== null) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime('now');
        }

        return $this;
    }

    /**
     * @return File|null
     */
    public function getAttachmentFile()
    {
        return $this->attachmentFile;
    }

    /**
     * @param string $attachmentName
     *
     * @return Product
     */
    public function setAttachmentName($attachmentName)
    {
        $this->attachmentName = $attachmentName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAttachmentName()
    {
        return $this->attachmentName;
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
     * Set updatedAt.
     *
     * @param \DateTime $updatedAt
     *
     * @return Attachment
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setAttachmentSize(?int $attachmentSize): void
    {
        $this->attachmentSize = $attachmentSize;
    }

    public function getAttachmentSize(): ?int
    {
        return $this->attachmentSize;
    }

    /**
     * NE FONCTIONNE PAS.
     *
     * @return type
     */
    public function getFileSize()
    {
        $filesize = 0;

        $file = $this->getAttachmentFile();
        $fileOk = false;
        if (file_exists($file)) {
            $pathParts = pathinfo($file);
            $extension = $pathParts['extension'];
            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    if (false !== imagecreatefromjpeg($file)) {
                        $fileOk = true;
                    }
                    break;
                case 'png':
                    if (false !== imagecreatefrompng($file)) {
                        $fileOk = true;
                    }
                    break;
                case 'gif':
                    if (false !== imagecreatefromgif($file)) {
                        $fileOk = true;
                    }
                    break;
            }
            if ($fileOk) {
                $filesize = filesize($file);
            }
        }

        return $this->fileSizeConvert($filesize);
    }

    /**
     * @param type $bytes
     *
     * @return string
     */
    private function fileSizeConvert($bytes)
    {
        $result = null;
        $bytes = floatval($bytes);
        $arBytes = [
          0 => [
              'UNIT' => 'TB',
              'VALUE' => pow(1024, 4),
          ],
          1 => [
              'UNIT' => 'GB',
              'VALUE' => pow(1024, 3),
          ],
          2 => [
              'UNIT' => 'MB',
              'VALUE' => pow(1024, 2),
          ],
          3 => [
              'UNIT' => 'KB',
              'VALUE' => 1024,
          ],
          4 => [
              'UNIT' => 'B',
              'VALUE' => 1,
          ],
        ];

        foreach ($arBytes as $arItem) {
            if ($bytes >= $arItem['VALUE']) {
                $result = $bytes / $arItem['VALUE'];
                $result = str_replace('.', ',', strval(round($result, 2))) . ' ' . $arItem['UNIT'];
                break;
            }
        }

        return $result;
    }
}
