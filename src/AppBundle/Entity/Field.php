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

/**
 * @ORM\Table(name="fields")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\FieldRepository")
 */
class Field {
    
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
     * @ORM\Column(name="parent_field_id", type="integer")
     */
    private $parentFieldId;
   

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $label;

    /**
     * @ORM\Column(name="field_type", type="string", length=64)
     */
    private $fieldType;

    /**
     * @ORM\Column(name="in_title", type="boolean", length=1)
     */
    private $inTitle = 0;

    /**
     * @ORM\Column(name="is_filter", type="boolean", length=1)
     */
    private $isFilter = 0;

    /**
     * @ORM\Column(name="is_sorter", type="boolean", length=1)
     */
    private $isSorter = 0;

    /**
     * @ORM\Column(name="field_options", type="string", length=4096)
     */
    private $fieldOptions;


    /**
     * @ORM\ManyToOne(targetEntity="ItemType", inversedBy="fields")
     * @ORM\JoinColumn(name="item_type_id", referencedColumnName="id")
     */
    private $itemType;


    /**
     * @ORM\ManyToOne(targetEntity="ItemType", inversedBy="fields")
     * @ORM\JoinColumn(name="field_item_type_id", referencedColumnName="id")
     */
    private $fieldItemType;

    /**
     * @ORM\Column(name="field_order", type="integer")
     */
    private $fieldOrder = 0;

    /**
     * @ORM\Column(name="visible_to_restricted_users", type="boolean", length=1)
     */
    private $visibleToRestrictedUsers = 0;

    /**
     * @var IFieldMeta
     */
    protected $meta = null;

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var AppBundle\Fields\Meta\FieldMetaFactory
     */
    protected $fieldMetaFactory;

    /*
    public function initServiceEntity(LoggerInterface $logger,
                                      FieldMetaFactory $fieldMetaFactory)
    {
        $this->logger = $logger;
        $this->fieldMetaFactory;
    }
    */

    private $parsedOptions;

    public function getFieldOptions() {
        if($this->parsedOptions == null)
        {
            $this->parsedOptions = new \stdClass();
            if(strlen($this->fieldOptions) > 2 && substr($this->fieldOptions[0], 0, 1) == '{') {
                $options = json_decode($this->fieldOptions);
                if(!is_null($options)) {
                   $this->parsedOptions = $options;
                }
            }
        }
        return $this->parsedOptions;
    }

    public function option($name, $default='')
    {
        $options = $this->getFieldOptions();
        if(isset($options->{$name})) {
            return $options->{$name};
        } else {
            return $default;
        }
    }

    /**
     * Load an appropriate meta class for the field type and store it's results on
     * a hidden property.
     */
    public function loadMeta(LoggerInterface $logger,
                                      FieldMetaFactory $fieldMetaFactory)
    {
        $this->logger = $logger;
        $this->fieldMetaFactory = $fieldMetaFactory;

        $class = '\\AppBundle\\Fields\\Meta\\'.ucwords($this->fieldType).'Meta';
        if(class_exists($class)) {
            $this->meta = $this->fieldMetaFactory->create($class);
            $this->meta->load($this);
        }
    }

    /**
     * Get the meta object for this field.
     */
    public function getMeta()
    {
        return $this->meta;
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
     * @return Field
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
     * @return Field
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
        return $this->itemType ? $this->itemType->getId() : 0;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Field
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
     * Set label
     *
     * @param string $label
     *
     * @return Field
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
     * Set fieldType
     *
     * @param string $fieldType
     *
     * @return Field
     */
    public function setFieldType($fieldType)
    {
        $this->fieldType = $fieldType;

        return $this;
    }

    /**
     * Get fieldType
     *
     * @return string
     */
    public function getFieldType()
    {
        return $this->fieldType;
    }


    /**
     * Set fieldOptions
     *
     * @param string $fieldOptions
     *
     * @return Field
     */
    public function setFieldOptions($fieldOptions)
    {
        $this->fieldOptions = $fieldOptions;

        return $this;
    }

    /**
     * Set inTitle
     *
     * @param boolean $inTitle
     *
     * @return Field
     */
    public function setInTitle($inTitle)
    {
        $this->inTitle = $inTitle;

        return $this;
    }

    /**
     * Get inTitle
     *
     * @return boolean
     */
    public function getInTitle()
    {
        return $this->inTitle;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Entity\ItemType $product
     *
     * @return Field
     */
    public function setProduct(\AppBundle\Entity\ItemType $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Entity\ItemType
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set itemType
     *
     * @param \AppBundle\Entity\ItemType $itemType
     *
     * @return Field
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

    /**
     * Set fieldItemType
     *
     * @param \AppBundle\Entity\ItemType $fieldItemType
     *
     * @return Field
     */
    public function setFieldItemType(\AppBundle\Entity\ItemType $fieldItemType = null)
    {
        $this->fieldItemType = $fieldItemType;

        return $this;
    }

    /**
     * Get fieldItemType
     *
     * @return \AppBundle\Entity\ItemType
     */
    public function getFieldItemType()
    {
        return $this->fieldItemType;
    }

    /**
     * Set isFilter
     *
     * @param boolean $isFilter
     *
     * @return Field
     */
    public function setIsFilter($isFilter)
    {
        $this->isFilter = $isFilter;

        return $this;
    }

    /**
     * Get isFilter
     *
     * @return boolean
     */
    public function getIsFilter()
    {
        return $this->isFilter;
    }


    /**
     * Set isSorter
     *
     * @param boolean $isSorter
     *
     * @return Field
     */
    public function setIsSorter($isSorter)
    {
        $this->isSorter = $isSorter;

        return $this;
    }

    /**
     * Get isSorter
     *
     * @return boolean
     */
    public function getIsSorter()
    {
        return $this->isSorter;
    }


    /**
     * Set fieldOrder
     *
     * @param boolean $fieldOrder
     *
     * @return Field
     */
    public function setFieldOrder($fieldOrder)
    {
        $this->fieldOrder = $fieldOrder;

        return $this;
    }

    /**
     * Get fieldOrder
     *
     * @return boolean
     */
    public function getFieldOrder()
    {
        return $this->fieldOrder;
    }

    /**
     * Set parentFieldId
     *
     * @param integer $parentFieldId
     *
     * @return Field
     */
    public function setParentFieldId($parentFieldId)
    {
        $this->parentFieldId = $parentFieldId;

        return $this;
    }

    /**
     * Get parentFieldId
     *
     * @return integer
     */
    public function getParentFieldId()
    {
        return $this->parentFieldId;
    }

    /**
     * Set visibleToRestrictedUsers
     *
     * @param boolean $visibleToRestrictedUsers
     *
     * @return Field
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
}
