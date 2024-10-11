<?php

namespace PostparcBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Language.
 *
 * @ORM\Table(name="language")
 * @ORM\Entity(repositoryClass="PostparcBundle\Repository\LanguageRepository")
 */
class Language
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
     * @ORM\Column(name="locale", type="string", length=255)
     */
    private $locale;

    /**
     * @var string
     *
     * @ORM\Column(name="langType", type="string", length=255)
     */
    private $langType;

    /**
     * @var string
     *
     * @ORM\Column(name="territory", type="string", length=255)
     */
    private $territory;

    /**
     * @var string
     *
     * @ORM\Column(name="frenchName", type="string", length=255)
     */
    private $frenchName;

    /**
     * @var string
     *
     * @ORM\Column(name="englishName", type="string", length=255)
     */
    private $englishName;

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
     * Set locale.
     *
     * @param string $locale
     *
     * @return Language
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set langType.
     *
     * @param string $langType
     *
     * @return Language
     */
    public function setLangType($langType)
    {
        $this->langType = $langType;

        return $this;
    }

    /**
     * Get langType.
     *
     * @return string
     */
    public function getLangType()
    {
        return $this->langType;
    }

    /**
     * Set territory.
     *
     * @param string $territory
     *
     * @return Language
     */
    public function setTerritory($territory)
    {
        $this->territory = $territory;

        return $this;
    }

    /**
     * Get territory.
     *
     * @return string
     */
    public function getTerritory()
    {
        return $this->territory;
    }

    /**
     * Set frenchName.
     *
     * @param string $frenchName
     *
     * @return Language
     */
    public function setFrenchName($frenchName)
    {
        $this->frenchName = $frenchName;

        return $this;
    }

    /**
     * Get frenchName.
     *
     * @return string
     */
    public function getFrenchName()
    {
        return $this->frenchName;
    }

    /**
     * Set englishName.
     *
     * @param string $englishName
     *
     * @return Language
     */
    public function setEnglishName($englishName)
    {
        $this->englishName = $englishName;

        return $this;
    }

    /**
     * Get englishName.
     *
     * @return string
     */
    public function getEnglishName()
    {
        return $this->englishName;
    }
}
