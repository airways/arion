<?php

/*
 * @package ArionCRM
 * @author Isaac Raway <iraway@metasushi [dot] com>
 * @author Antoinette Smith <asmith@metasushi [dot] com>
 * @link http://arioncrm.com/
 * @copyright (c)2015-2022. MetaSushi, LLC. All rights reserved. Your use of this software in any way indicates agreement
 * to the software license available currenty at http://arioncrm.com/ 
 * This open source edition is released under GPL 3.0. available at https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;
use Doctrine\ORM\Mapping as ORM;
use AppBundle\Fields\Meta\FieldMetaFactory;
use AppBundle\Service\TextMacroService;
// use GorHill\FineDiff\FineDiff;
use Icap\HtmlDiff\HtmlDiff;

/**
 * @ORM\Table(name="item_versions")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ItemVersionRepository")
 */
class ItemVersion {
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /** 
     * @ORM\ManyToOne(targetEntity="Item")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id")
     */
    private $item;

    /**
     * @ORM\Column(name="account_id", type="integer")
     */
    private $accountId;

    /**
     * @ORM\ManyToOne(targetEntity="ItemType")
     * @ORM\JoinColumn(name="item_type_id", referencedColumnName="id")
     */
    private $itemType;

    /**
     * @ORM\Column(name="field_count", type="integer")
     */
    private $fieldCount;

    /**
     * @ORM\Column(type="integer")
     */
    private $ver;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $title;

    /**
     * Fields from Fields model
     */
    protected $fields = array();

    /**
     * Meta data from ItemValues model 
     * @ORM\Column(name="meta", type="json") 
     */
    protected $itemValuesMeta = array();

    /**
     * Values from ItemValues model 
     * @ORM\Column(name="data", type="json")
     */
    private $itemValues = array();


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Iintended for unit tests
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get item id
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set item id
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;
    }

    /**
     * Set accountId
     *
     * @param integer $accountId
     *
     * @return Item
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Get accountId
     *
     * @return integer
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * Set itemTypeId
     *
     * @param integer $itemTypeId
     *
     * @return Item
     */
    public function setItemTypeId($itemTypeId)
    {
        $this->itemTypeId = $itemTypeId;

        return $this;
    }

    /**
     * Get itemTypeId
     *
     * @return integer
     */
    public function getItemTypeId()
    {
        return $this->itemTypeId;
    }

    /**
     * Set fieldCount
     *
     * @param integer $fieldCount
     *
     * @return Item
     */
    public function setFieldCount($fieldCount)
    {
        $this->fieldCount = $fieldCount;

        return $this;
    }

    /**
     * Get fieldCount
     *
     * @return integer
     */
    public function getFieldCount()
    {
        return $this->fieldCount;
    }

    /**
     * Set ver
     *
     * @param integer $ver
     *
     * @return Item
     */
    public function setVer($ver)
    {
        $this->ver = $ver;

        return $this;
    }

    /**
     * Get ver
     *
     * @return integer
     */
    public function getVer()
    {
        return $this->ver;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Item
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set itemType
     *
     * @param \AppBundle\Entity\ItemType $itemType
     *
     * @return Item
     */
    public function setItemType(\AppBundle\Entity\ItemType $itemType = null)
    {
        $this->itemType = $itemType;

        return $this;
    }

    /**
     * Get itemType
     *
     * @return \AppBundle\Entity\ItemType
     */
    public function getItemType()
    {
        return $this->itemType;
    }
}

