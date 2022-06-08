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

 
namespace AppBundle\Domain;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use AppBundle\Service\AuthService;

class Item {
    use ContainerAwareTrait;

    /**
     * @var array of \AppBundle\Fields\IDomainField
     */
    protected $fields = [];

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @var \AppBundle\Entity\Item
     */
	protected $modelItem;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $view
     */
    protected $view;

    /**
     * @var \AppBundle\Service\AuthService
     */
    protected $session;

    protected $extraTitleValues = [];

	public function __construct(LoggerInterface $logger, \AppBundle\Entity\Item $modelItem,
                                array $extraTitleValues,
                                EngineInterface $view, AuthService $session) {
		$this->logger = $logger;

		$this->modelItem = $modelItem;
        $this->extraTitleValues = $extraTitleValues;
        $this->view = $view;
        $this->session = $session;
	}

    /**
     * Get IDomainField nstances for the item
     *
     * @return array of IDomainField
     */
    public function fields()
    {
        // See if we have already loaded fields for the item
        if(count($this->fields) == 0)
        {
            $allSubFields = [];

            // If not, get the list of Field records and loop over them to create new domain field objects
            foreach($this->modelItem->getFields() as $modelField)
            {
                // Get the domain object for this field and fieldtype, add to result
                $fieldName = ucwords($modelField->getFieldType());
                
                // New field types should always be loaded from the container rather than through new!
                // TODO: Convert ALL field types to use container loading
                if($fieldName == 'File') {
                    $serviceName = strtolower($fieldName).'Field';
                    $fieldDomain = $this->container->get($serviceName);
                } else { 
                    $class = '\\AppBundle\\Fields\\'.ucwords($fieldName).'Field';
                    $fieldDomain = new $class($this->logger, $this->view, $this->session);
                }
                $fieldDomain->setModelField($modelField);
                $fieldDomain->setItem($this);
                
                // If the field is a subfield, save it for attachment to it's parent
                if($modelField->getParentFieldId() != 0)
                {
                    $allSubFields[] = $fieldDomain;
                } else {
                    $this->fields[$modelField->getId()] = $fieldDomain;
                }
            }

            // Sorts all subfields, interleaved, but will be extracted to the correct parent in the
            // right order, preventing the parent field from needing to do any sorting
            usort($allSubFields, [$this, 'sortFieldsCompare']);

            // Asssign subfields to their parents
            foreach($allSubFields as $subField)
            {
                $subField->setParentField($this->fields[$subField->getParentFieldId()]);
                $this->fields[$subField->getParentFieldId()]->addSubField($subField);
            }
        }
        
        usort($this->fields, [$this, 'sortFieldsCompare']);
        return $this->fields;
    }

    public function hasField($name)
    {
        $name = strtolower($name);
        foreach($this->fields() as $field)
        {
            if(strtolower($field->name) == $name)
            {
                return true;
            }
        }
        return false;
    }

    // public function getData()
    // {
    //     $data = [];
    //     foreach($this->fields() as $field)
    //     {
    //         $data[$field->name] = $this->{$field->name};

    //         if($field->fieldType == 'File')
    //         {
    //             $files[$field->name] = [];
    //         }
    //     }
    //     return [$data, $files];
    // }

    public function getValues()
    {
        return $this->modelItem->getValues();
    }

    public function getExtraTitleValues()
    {
        return $this->extraTitleValues;
    }

    /**
     * Used to sort fields in order defined by field_order
     */
    private function sortFieldsCompare($a, $b)
    {
        return $a->getFieldOrder() - $b->getFieldOrder();
        //if($a->getFieldOrder() == $b->getFieldOrder()) return 0;
        //return ($a->getFieldOrder() < $b->getFieldOrder()) ? -1 : 1;
    }

    /**
     * Called by ItemsService when a create() request is made and a new item was created.
     *
     * @param $key
     * @param $value
     */
    public function onAfterCreateItem()
    {
        return $this->modelItem->onAfterCreateItem();
    }

    /**
     * Called by ItemsService when a get() request is made for an individual record, trigger loading
     * of additional data to populate the modelItem's values array.
     *
     * @param $key
     * @param $value
     */
    public function onAfterGetItem()
    {
        $this->modelItem->initServiceEntity($this->logger, $this->container->get('itemRepository'), $this->container->get('itemValueRepository'),
                                            $this->container->get('userRepository'), $this->container->get('fieldMetaFactory'), $this->container->get('macroService'));
        return $this->modelItem->onAfterGetItem();
    }

    /**
     * Get a field either from the base modelItem record, or in the modelItem's values array
     *
     * @param $key
     * @param $value
     */
    public function get($key)
    {
        if($this->modelItem->hasValue($key)) {
            return $this->modelItem->getValue($key);
        } else {
            if(method_exists($this->modelItem, 'get'.ucwords($key))) {
                return $this->modelItem->{'get'.ucwords($key)}();
            }
        }
    }
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Update a field either on the base modelItem record, or in the modelItem's values array
     *
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        if($this->modelItem->hasValue($key)) {
            $this->modelItem->setValue($key, $value);
        } else {
            return $this->modelItem->$key = $value;
        }
    }
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    }

    public function hasValue($key)
    {
        return $this->modelItem->hasValue($key);
    }
    
    /**
     * Return values meta data -- who created the value, datetime it was created, etc.
     *
     */
    public function getValuesMeta($key)
    {
        return $this->modelItem->getValuesMeta($key);
    }

    /**
     * Save changes from API call or form post to the item
     *
     * @param array $data
     * @param array $files
     * @param array $cmd
     */
    public function update($prev_ver, $userId, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId,
                           array $data, array $files, array $cmd)
    {
        // var_dump($data);
        // echo 'update';
        // exit;
        foreach($this->fields() as $field)
        {
            if($field->visibleToRestrictedUsers || (!$restrictedUserOwnerItemType && !$restrictedUserOwnerItemId))
            {
                $data = $field->update($prev_ver, $data, $files, $cmd);
            }
        }

        foreach($data as $key => $value)
        {
            if($this->modelItem->hasValue($key)
                || isset($this->modelItem->$key)
                && $this->$key != $value)
            {
                $this->$key = $value;
            }
        }
        
        return $this->modelItem->save($prev_ver, $userId, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);
    }

    public function getModelItem()
    {
        return $this->modelItem;
    }

    /**
     * Get the change log from last save()
     */
    public function getChangeLog($restricted=true)
    {
        return $this->modelItem->getChangeLog($restricted);
    }

    public function itemHasConflicts()
    {
        return $this->modelItem->itemHasConflicts($fieldId);
    }
    
    public function getFieldConflicts($fieldId)
    {
        return $this->modelItem->getFieldConflicts($fieldId);
    }

    public function fieldHasConflicts($fieldId)
    {
        return $this->modelItem->fieldHasConflicts($fieldId);
    }

    public function getError()
    {
        return $this->modelItem->getError();
    }

    public function getErrorMessage()
    {
        return $this->modelItem->getErrorMessage();
    }
    
    public function getCreatedBy()
    {
        return @$this->modelItem->getItemMetaObject()->createdBy;
    }

    public function getCreatedAt()
    {
        return @$this->modelItem->getItemMetaObject()->createdAt;
    }

    public function getOwnerId()
    {
        return $this->modelItem->getOwnerId();
    }
}
