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

namespace AppBundle\Fields;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use AppBundle\Entity\Field;
use AppBundle\Domain\Item;
use AppBundle\Service\AuthService;

class BaseDomainField {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \AppBundle\Entity\Field
     */
    protected $fieldModel;

    /**
     * @var \AppBundle\Domain\Item
     */
    protected $item;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $view
     */
    protected $view;

    /**
     * @var \AppBundle\Service\AuthService
     */
    protected $session;

    /**
     * @var array of BaseDomainField
     */
    protected $subFields = [];

    public $id;
    public $label;
    public $name;
    public $fieldType;
    public $field_options = null;
    public $visibleToRestrictedUsers = true;

    private $isSubField = false;
    private $parentField = null;

    public function __construct(LoggerInterface $logger, EngineInterface $view, AuthService $session)
    {
        $this->logger = $logger;
        $this->view = $view;
        $this->session = $session;
    }

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function setModelField(Field $fieldModel)
    {
        $this->fieldModel = $fieldModel;
        $this->id = $fieldModel->getId();
        $this->label = $fieldModel->getLabel();
        $this->name = $fieldModel->getName();
        $this->field_options = $fieldModel->getFieldOptions();
        $this->fieldType = $fieldModel->getFieldType();
        $this->visibleToRestrictedUsers = $fieldModel->getVisibleToRestrictedUsers();
    }

    public function addSubField(BaseDomainField $subField)
    {
        $this->subFields[] = $subField;
    }

    public function setParentField(BaseDomainField $parentField)
    {
        $this->isSubField = true;
        $this->parentField = $parentField;
    }

    public function getSubFields()
    {
        return $this->subFields;
    }

    public function breakRow()
    {
        return true;
    }

    public function option($name, $default='')
    {
        if(isset($this->field_options->{$name})) {
            return $this->field_options->{$name};
        } else {
            return $default;
        }
    }

    /**
     * Called to process data prior to saving a new item.
     */
    public function update($prevVer, array $data, array $files, array $cmd)
    {
        return $data;
    }

    protected function getValuesAndMeta()
    {
        /*if(!isset($this->item->{$this->name}))
        {
            //return [['test.jpg'], [['createdAt' => time(), 'createdBy' => 'You']]];
            return [[], []];
        }*/

        $values = $this->item->{$this->name};
        if(!is_array($values)) $values = [$values];
        if(!is_array($values[0])) $values = [$values];

        $valuesMeta = $this->item->getValuesMeta($this->name);
        if(!is_array($valuesMeta)) $valuesMeta = [$valuesMeta];
        if(count($valuesMeta) > 0) {
            if(!isset($valuesMeta[0]) || !is_array($valuesMeta[0])) $valuesMeta = [$valuesMeta];
        }

        foreach(array_keys($values) as $subFieldId)
        {
            if(!array_key_exists($subFieldId, $valuesMeta))
            {
                $valuesMeta[$subFieldId] = [];
            }

            while(count($valuesMeta[$subFieldId]) < count($values[$subFieldId])) {
                $valuesMeta[$subFieldId][] = (object)[
                    'createdAt' => time(),
                    'createdBy' => 'Unknown',
                    'createdByUserId' => $this->session->getUserId(),
                    ];
            }
        }

        return [$values, $valuesMeta];
    }

    public function isFilter()
    {
        return $this->fieldModel->getIsFilter();
    }

    public function isSorter()
    {
        return $this->fieldModel->getIsSorter();
    }

    public function getFieldItemType()
    {
        return $this->fieldModel->getFieldItemType();
    }

    public function getFieldOrder()
    {
        return $this->fieldModel->getFieldOrder();
    }

    public function getParentFieldId()
    {
        return $this->fieldModel->getParentFieldId();
    }

    /**
     * Construct a standard field name for an input element based on the itemId and filterMode
     */
    protected function getInputFieldName($filterMode=false)
    {
        if($filterMode) {
            $fieldName = 'filters.'.$this->item->itemType->getPluralName().'.'.$this->name;
        } else {
            $fieldName = 'item['.$this->item->get('id').']'.
                            ($this->isSubField ? '['.$this->parentField->name .']': '').'['.$this->name.']'.
                            ($this->isSubField && $this->parentField->fieldType == 'Template' ? '[]' : '');
        }
        return $fieldName;
    }

    public function cssClass($i)
    {
        if($i == 0)
        {
            return 'col-xs-11 col-sm-11 col-md-11';
        } else {
            return 'col-xs-12 col-sm-12 col-md-12';
        }
    }
}
