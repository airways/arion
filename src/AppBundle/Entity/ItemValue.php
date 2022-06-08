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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="item_values")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ItemValueRepository")
 */
class ItemValue {
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="account_id", type="integer")
     */
    private $accountId;

    /**
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @ORM\Column(name="item_id", type="integer")
     */
    private $itemId;

    /**
     * @ORM\Column(type="integer")
     */
    private $ver;

    /**
     * @ORM\Column(name="prev_ver", type="integer")
     */
    private $prevVer;

    /**
     * @ORM\Column(name="sub_field_count", type="integer")
     */
    private $subFieldCount;

    /**
     * @ORM\Column(name="sub_value_count", type="integer")
     */
    private $subValueCount;

    /**
     * @ORM\Column(name="field_id", type="integer")
     */
    private $fieldId;

    /**
     * @ORM\Column(name="sub_field_id", type="integer")
     */
    private $subFieldId;

    /**
     * @ORM\Column(name="sub_value_id", type="integer")
     */
    private $subValueId;

    /**
     * @ORM\Column(type="string", length=21800)
     */
    private $value;


    /**
     * @ORM\Column(name="created_at", type="integer")
     */
    private $createdAt;
    
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
     * Set accountId
     *
     * @param integer $accountId
     *
     * @return ItemValue
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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return ItemValue
     */
    public function setItemId($itemId)
    {
        $this->itemId = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * Set ver
     *
     * @param integer $ver
     *
     * @return ItemValue
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
     * Set prevVer
     *
     * @param integer $prevVer
     *
     * @return ItemValue
     */
    public function setPrevVer($prevVer)
    {
        $this->prevVer = $prevVer;

        return $this;
    }

    /**
     * Get prevVer
     *
     * @return integer
     */
    public function getPrevVer()
    {
        return $this->prevVer;
    }

    /**
     * Set subFieldCount
     *
     * @param integer $subFieldCount
     *
     * @return ItemValue
     */
    public function setSubFieldCount($subFieldCount)
    {
        $this->subFieldCount = $subFieldCount;

        return $this;
    }

    /**
     * Get subFieldCount
     *
     * @return integer
     */
    public function getSubFieldCount()
    {
        return $this->subFieldCount;
    }

    /**
     * Set subValueCount
     *
     * @param integer $subValueCount
     *
     * @return ItemValue
     */
    public function setSubValueCount($subValueCount)
    {
        $this->subValueCount = $subValueCount;

        return $this;
    }

    /**
     * Get subValueCount
     *
     * @return integer
     */
    public function getSubValueCount()
    {
        return $this->subValueCount;
    }

    /**
     * Set fieldId
     *
     * @param integer $fieldId
     *
     * @return ItemValue
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    /**
     * Get fieldId
     *
     * @return integer
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Set subFieldId
     *
     * @param integer $subFieldId
     *
     * @return ItemValue
     */
    public function setSubFieldId($subFieldId)
    {
        $this->subFieldId = $subFieldId;

        return $this;
    }

    /**
     * Get subFieldId
     *
     * @return integer
     */
    public function getSubFieldId()
    {
        return $this->subFieldId;
    }

    /**
     * Set subValueId
     *
     * @param integer $subValueId
     *
     * @return ItemValue
     */
    public function setSubValueId($subValueId)
    {
        $this->subValueId = $subValueId;

        return $this;
    }

    /**
     * Get subValueId
     *
     * @return integer
     */
    public function getSubValueId()
    {
        return $this->subValueId;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return ItemValue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        // // Check if the value contains markup, if so, return as-is.
        // // If not, pass through nl2br and htmlentities first
        // if(!$this->getValueHasHTML())
        // {
        //     return \nl2br(\htmlentities($this->value));
        // } else {
            return $this->value;
        // }
    }

    public function getValueHasHTML()
    {
        if(strpos($this->value, '<div') === FALSE && strpos($this->value, '<span') === FALSE && strpos($this->value, '<p') === FALSE&& strpos($this->value, '<a') === FALSE)
        {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return ItemValue
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set createdAt
     *
     * @param integer $createdAt
     *
     * @return ItemValue
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return integer
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
}
