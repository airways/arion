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
 * @ORM\Table(name="item_types")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ItemTypeRepository")
 */
class ItemType {

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
     * @ORM\Column(name="sort_order", type="integer")
     */
    private $sortOrder;

    /**
     * @ORM\Column(name="name", type="string", length=128)
     */
    private $name;

    /**
     * @ORM\Column(name="plural_name", type="string", length=128)
     */
    private $pluralName;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $label;

    /**
     * @ORM\Column(name="plural_label", type="string", length=128)
     */
    private $pluralLabel;

    /**
     * @ORM\Column(name="are_users", type="boolean", length=1)
     */
    private $areUsers = 0;

    /**
     * @ORM\Column(name="own_users", type="boolean", length=1)
     */
    private $ownUsers = 0;

    /**
     * @ORM\OneToMany(targetEntity="Field", mappedBy="itemType")
     * @ORM\OrderBy({"fieldOrder" = "ASC"})
     */
    private $fields;

    /**
     * @ORM\Column(name="visible_to_restricted_users", type="boolean", length=1)
     */
    private $visibleToRestrictedUsers = true;

    /**
     * @ORM\Column(name="options", type="string", length=4096)
     */
    private $options;
    private $parsedOptions = null;

    public function getOptions()
    {
        if($this->parsedOptions == null)
        {
            $this->parsedOptions = new \stdClass();
                if(strlen($this->options) > 2 && substr($this->options[0], 0, 1) == '{') {
                $options = json_decode($this->options);
                if(!is_null($options)) {
                   $this->parsedOptions = $options;
                }
            }
        }
        return $this->parsedOptions;
    }

    public function option($key, $default=false)
    {
        $options = $this->getOptions();
        if(isset($options->$key)) return $options->$key;
        else return $default;
    }

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
     * @return ItemType
     */
    public function setAccountId($accountId)
    {
        $this->account_id = $accountId;

        return $this;
    }

    /**
     * Get accountId
     *
     * @return integer
     */
    public function getAccountId()
    {
        return $this->account_id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ItemType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Set pluralName
     *
     * @param string $pluralName
     *
     * @return ItemType
     */
    public function setPluralName($pluralName)
    {
        $this->pluralName = $pluralName;

        return $this;
    }

    /**
     * Get pluralName
     *
     * @return string
     */
    public function getPluralName()
    {
        return $this->pluralName;
    }

    /**
     * Set label
     *
     * @param string $label
     *
     * @return ItemType
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set pluralLabel
     *
     * @param string $pluralLabel
     *
     * @return ItemType
     */
    public function setPluralLabel($pluralLabel)
    {
        $this->pluralLabel = $pluralLabel;

        return $this;
    }

    /**
     * Get pluralLabel
     *
     * @return string
     */
    public function getPluralLabel()
    {
        return $this->pluralLabel;
    }

    

    /**
     * Set areUsers
     *
     * @param boolean $areUsers
     *
     * @return ItemType
     */
    public function setAreUsers($areUsers)
    {
        $this->areUsers = $areUsers;

        return $this;
    }

    /**
     * Get areUsers
     *
     * @return boolean
     */
    public function getAreUsers()
    {
        return $this->areUsers;
    }

    /**
     * Set ownUsers
     *
     * @param boolean $ownUsers
     *
     * @return ItemType
     */
    public function setOwnUsers($ownUsers)
    {
        $this->ownUsers = $ownUsers;

        return $this;
    }

    /**
     * Get ownUsers
     *
     * @return boolean
     */
    public function getOwnUsers()
    {
        return $this->ownUsers;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->itemType = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add itemType
     *
     * @param \AppBundle\Entity\Field $itemType
     *
     * @return ItemType
     */
    public function addItemType(\AppBundle\Entity\Field $itemType)
    {
        $this->itemType[] = $itemType;

        return $this;
    }

    /**
     * Remove itemType
     *
     * @param \AppBundle\Entity\Field $itemType
     */
    public function removeItemType(\AppBundle\Entity\Field $itemType)
    {
        $this->itemType->removeElement($itemType);
    }

    /**
     * Get itemType
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItemType()
    {
        return $this->itemType;
    }

    /**
     * Add field
     *
     * @param \AppBundle\Entity\Field $field
     *
     * @return ItemType
     */
    public function addField(\AppBundle\Entity\Field $field)
    {
        $this->fields[] = $field;

        return $this;
    }

    /**
     * Remove field
     *
     * @param \AppBundle\Entity\Field $field
     */
    public function removeField(\AppBundle\Entity\Field $field)
    {
        $this->fields->removeElement($field);
    }

    /**
     * Get fields
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFields()
    {
        return $this->fields;
    }


    /**
     * Set visibleToRestrictedUsers
     *
     * @param boolean $visibleToRestrictedUsers
     *
     * @return ItemType
     */
    public function setVisibleToRestrictedUsers($visibleToRestrictedUsers)
    {
        $this->visibleToRestrictedUsers = $visibleToRestrictedUsers;

        return $this;
    }

    /**
     * Get visibleToRestrictedUsers
     *
     * @return boolean
     */
    public function getVisibleToRestrictedUsers()
    {
        return $this->visibleToRestrictedUsers;
    }

    /**
     * Set sortOrder
     *
     * @param integer $sortOrder
     *
     * @return ItemType
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * Get sortOrder
     *
     * @return integer
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * Set options
     *
     * @param string $options
     *
     * @return ItemType
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }
}
