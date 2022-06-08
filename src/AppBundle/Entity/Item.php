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
 * @ORM\Table(name="items")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\ItemRepository")
 */
class Item {
    
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
     * @ORM\ManyToOne(targetEntity="ItemType")
     * @ORM\JoinColumn(name="item_type_id", 
     *     referencedColumnName="id")
     */
    private $itemType;

    /**
     * @ORM\ManyToOne(targetEntity="ItemType")
     * @ORM\JoinColumn(name="owner_item_type_id", 
     *     referencedColumnName="id")
     */
    private $ownerItemType;

    /**
     * @ORM\ManyToOne(targetEntity="Item")
     * @ORM\JoinColumn(name="owner_item_id", 
     *     referencedColumnName="id")
     */
    private $ownerItem;

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
     * Values stored only for a single request. Often used by IField implementations to store related data.
     */
    protected $cacheValues = array();

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var AppBundle\Entity\ItemRepository
     */
    protected $itemsRepository;

    /**
     * @var AppBundle\Entity\ItemValueRepository
     */
    protected $itemValuesRepository;

    /**
     * @var AppBundle\Entity\UserRepository
     */
    protected $userRepository;

    /**
     * @var AppBundle\Service\AuthService
     */
    protected $authService;

    /**
     * @var AppBundle\Fields\Meta\FieldMetaFactory
     */

    protected $loaded = false;

    /**
     * Set by save logic based on how many inserts were needed to update the item.
     */
    protected $changeCount = 0;

    /**
     * @var AppBundle\Service\TextMacroService
     */
    protected $textMacroService;

    /**
     * Set by save logic based on what is modified in the item.
     */
    protected $changeLog = '';
    protected $restrictedChangeLog = '';
    protected $itemMetaObject;
    protected $error = 0;
    protected $errorMessage = '';
    protected $conflicts = [];

    public function initServiceEntity(LoggerInterface $logger,
                                      ItemRepository $items,
                                      ItemValueRepository $itemValuesRepository,
                                      UserRepository $users,
                                      FieldMetaFactory $fieldMetaFactory,
                                      TextMacroService $textMacroService)
    {
        $this->logger = $logger;
        $this->itemsRepository = $items;
        $this->itemValuesRepository = $itemValuesRepository;
        $this->users = $users;
        $this->fieldMetaFactory = $fieldMetaFactory;
        $this->usersRespository = $users;
        $this->textMacroService = $textMacroService;
    }

    private function loadMeta()
    {
        $this->fieldMetaFactory->setItemRepository($this->itemsRepository);
        foreach($this->itemType->getFields() as $field)
        {
            //$this->logger->debug(__METHOD__.'::'.$field->getName());
            $field->loadMeta($this->logger, $this->fieldMetaFactory);
            $this->fields[$field->getId()] = $field;
        }
        //$this->logger->debug(__METHOD__.'::complete');
    }

    /**
     * Called when an individual item is created.
     */
    public function onAfterCreateItem()
    {
    }

    /**
     * Called when an mock filter item is created.
     */
    public function onAfterCreateFilterItem()
    {
        // Load field meta data
        $this->loadMeta();
    }

    /**
     * Called when an individual item is loaded. This is the only time when we want
     * to load the custom fields for the item since this takes some additional
     * work. We do NOT do this work when a list of items is loaded, for instance.
     *
     * @param integer $account_id account this item is loaded under
     * @return nothing
     */ 
    public function onAfterGetItem()
    {
        //$this->logger->debug(__METHOD__.':: '.$this->id.'<<<<<<<<<<<<<<<<<<<<<<<<<<');
        if($this->loaded) return;

        $this->loaded = true;

        // Load field meta data
        $this->loadMeta();

        if(is_null($this->itemValues)) $this->itemValues = [];
        if(is_null($this->itemValuesMeta)) $this->itemValuesMeta = [];

        if(count($this->itemValues) == 0 || $this->getOwnerItem() == NULL) {

            $this->logger->warning(__METHOD__.'::item '.$this->id.' has no data cached, loading from item_values...');
            // Load first version's values to find who created item
            foreach($this->itemValuesRepository->findItemValues($this->accountId, $this->id, 0) as $itemValue)
            {
                if(isset($this->fields[$itemValue->getFieldId()]))
                {
                    $field = $this->fields[$itemValue->getFieldId()];

                    $user = $this->users->getUser($this->accountId, $itemValue->getUserId());

                    $this->itemMetaObject = (object)[
                            'createdAt' => $itemValue->getCreatedAt(),
                            'createdBy' => !is_null($user) ? $user->getName() : 'Unknown',
                        ];
                    break;
                }
            }

            // Load item values
            foreach($this->itemValuesRepository->findItemValues($this->accountId, $this->id) as $itemValue)
            {
               
                //$field = $this->fieldsTable->getField($this->accountId, $itemValue->field_id);

                if(isset($this->fields[$itemValue->getFieldId()]))
                {
                    $field = $this->fields[$itemValue->getFieldId()];

                    $user = $this->users->getUser($this->accountId, $itemValue->getUserId());
                    $metaObject = (object)[
                            'createdAt' => $itemValue->getCreatedAt(),
                            'createdBy' => !is_null($user) ? $user->getName() : 'Unknown',
                            'createdByUserId' => $itemValue->getUserId(),
                        ];
                    

                    // If this is part of a sequence of sub_fields or sub_values, store each value in a nested array
                    // The first level of the nested array represents sub_fields. The second level within this is the
                    // array of sub_values for each sub_field. If a field has no sub_fields, then it
                    // is the same as if it had a single sub_field -- a single array of sub_values.
                    if($itemValue->getSubFieldCount() > 1 || $itemValue->getSubValueCount() > 1)
                    {
                        /** Values **/
                        /** Generate the nested array for each field by assembling all of it's values into a two-deep nested array **/

                        // If this is the first sub_field for this field, create a blank array to store sub_field arrays
                        if(!array_key_exists($field->getName(), $this->itemValues))
                        {
                            $this->itemValues[$field->getName()] = [];
                        } else if(!is_array($this->itemValues[$field->getName()])) {
                            // Previous version we're merging with had only one sub_field, wrap first value in array
                            // @TODO: This may not work correctly with sub_values.
                            $this->itemValues[$field->getName()] = [[$this->itemValues[$field->getName()]]];
                        }

                        // If this is the first sub_value for this field/sub_field, create a blank array to store sub_values
                        if(!array_key_exists($itemValue->getSubFieldId(), $this->itemValues[$field->getName()]))
                        {
                            $this->itemValues[$field->getName()][(int)$itemValue->getSubFieldId()] = [];
                        }

                        // Add value to sub_field array
                        $this->itemValues[$field->getName()][(int)$itemValue->getSubFieldId()][] = $itemValue->getValue();

                        /** Meta (user info and timestamps) **/
                        /** Repeat above logic for meta objects **/
                        if(!array_key_exists($field->getName(), $this->itemValuesMeta))
                        {
                            $this->itemValuesMeta[$field->getName()] = [];
                        } else if(!is_array($this->itemValuesMeta[$field->getName()])) {
                            // Previous version we're merging with had only one subvalue, wrap first value in array
                            // @TODO: This may not work correctly with sub_values.
                            $this->itemValuesMeta[$field->getName()] = [[$this->itemValuesMeta[$field->getName()]]];
                        }

                        if(!array_key_exists($itemValue->getSubFieldId(), $this->itemValuesMeta[$field->getName()]))
                        {
                            $this->itemValuesMeta[$field->getName()][(int)$itemValue->getSubFieldId()] = [];
                        }


                        $this->itemValuesMeta[$field->getName()][(int)$itemValue->getSubFieldId()][] = $metaObject;

                    } else {
                        // Single value
                        //$this->logger->debug('single value found for field::'.$field->getId().'::'.$field->getName());
                        //$this->logger->debug(print_r($itemValue, true));
                        $this->itemValues[$field->getName()] = $itemValue->getValue();

                        $this->itemValuesMeta[$field->getName()] = $metaObject;

                    }

                    
                } else {
                    $this->logger->error(sprintf(__METHOD__.'::ERROR invalid field ID %d in item_value ID %d',
                                            $itemValue->getFieldId(), $itemValue->getId()));
                }
            }

            $this->logger->warning(__METHOD__.'::item '.$this->id.' load from item_values complete');
            $this->getOwnerId();
            $this->itemsRepository->saveItem($this);
            $this->logger->warning(__METHOD__.'::item '.$this->id.' saved');
        }
        $itemMetaObject = (object)[
                'createdAt' => time(),
                'createdBy' => 'Unknown'
            ];

        ($findMetaObject = function (array &$array) use(&$findMetaObject, $itemMetaObject) {
            foreach($array as $field => $metaObject) {
                if(is_array($metaObject) && !array_is_assoc($metaObject)) {
                    $findMetaObject($metaObject);
                } else {
                    $metaObject = (object)$metaObject;
                    $array[$field] = $metaObject;
                    if($metaObject->createdAt <= $itemMetaObject->createdAt) {
                        $itemMetaObject->createdAt = $metaObject->createdAt;
                        $itemMetaObject->createdBy = $metaObject->createdBy ? $metaObject->createdBy : 'Unknown';
                    }
                }
            }
            return $array;
        })($this->itemValuesMeta);

        /*var_dump($itemMetaObject);
        var_dump($this->itemValuesMeta);
        exit;*/
        $this->itemMetaObject = $itemMetaObject;

        //dump($this->itemValuesMeta); exit;
    }

    /**
     * Return values meta data -- who created the value, datetime it was created, etc.
     *
     */
    public function getValuesMeta($key)
    {
        if(array_key_exists($key, $this->itemValuesMeta)) {
            return $this->itemValuesMeta[$key];
        } else {
            return [];
        }
    }


    public function getItemMetaObject()
    {
        return $this->itemMetaObject;
    }
    
    public function save($prev_ver=0, $userId, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId)
    {
        $result = true;
        $changeCount = 0;
        $new_ver = time();
        $newTitle = '';
        $fieldCount = 0;
        $titleCount = 0;
        $newRows = [];

        $this->loadMeta();
        

        $currentUser = $this->users->getUser($this->accountId, $userId);

        // Get existing value rows for prev_ver version (prev_ver submitted with form data)
        if(is_null($this->id)) {
            // Save blank item
            $newTitle = 'New Item ';
            $existingValues = [];
            $currentValues = [];
            $this->fieldCount = $fieldCount;
            $this->ver = $new_ver;
            $this->itemsRepository->saveItem($this);
        } else {
            // Values from prev_ver version
            $existingValues = $this->itemValuesRepository->findItemValues($this->accountId, $this->id, $prev_ver);
            // Values from whatever the latest version is
            $currentValues = $this->itemValuesRepository->findItemValues($this->accountId, $this->id);
        }



        foreach($this->itemType->getFields() as $field)
        {
            $this->logger->debug('______ field::'.$field->getName());

            if($field->getFieldType() == "Relationship" && $field->getFieldItemType()->getOwnUsers()) {
                $ownerItemId = (int)$this->getValue($field->getName());
                if($ownerItemId) {
                    $this->setOwnerItemType($field->getFieldItemType());
                    $this->setOwnerItem($this->itemsRepository->getItem($this->accountId, $ownerItemId, 0, 0, true));
                }
            }
            if(!$field->getVisibleToRestrictedUsers() && ($restrictedUserOwnerItemType || $restrictedUserOwnerItemId))
            {
                $this->logger->debug('______ skip field -- current user is restricted');
                continue;
            }

            $fieldId = $field->getId();
            $fieldCount++;

            // Get an array of sub_fields values to save, or create a fake array if the value for this
            // field doesn't contain sub_fields.
            if(!array_key_exists($field->getName(), $this->itemValues))
            {
                $subFields = [['']];
            } else {
                if(!is_array($this->itemValues[$field->getName()]))
                {
                    $subFields = [[$this->itemValues[$field->getName()]]];
                } else {
                    $subFields = $this->itemValues[$field->getName()];
                }
            }

            // Set the title for the item to the first sub_field's first sub_value
            if($field->getInTitle())
            {
                if(!is_array($subFields)) exit('Unknown internal error! 160116');

                // We only set the first one now, originally it would add all fields with the bit set to the title
                // They are now rendered in the view instead under the item's title
                $titleCount++;
                if($titleCount == 1)
                {
                    // Get the first sub_field's first sub_value
                    $addToTitle = strip_tags($subFields[0][0]);
                    if(($n = strpos($addToTitle, "\n")) !== FALSE)
                    {
                        $addToTitle = substr($addToTitle, 0, $n);
                    }

                    // Trim the length down to 103 characters if needed
                    if(strlen($addToTitle) > 100) {
                        $addToTitle = substr($addToTitle, 0, 100).'...';
                    }
                    
                    // Don't bother if it's blank
                    if($addToTitle == '') continue;
                    $newTitle .= $addToTitle.' ';
                }
            }

            // Count new number of sub_fields within this field so we can detect a count drop, in which case we
            // trigger a full write of all sub_values
            $newSubFieldCount = count($subFields);
            $this->logger->debug('__________ newSubFieldCount::'.count($subFields));
            $this->logger->debug(json_encode($subFields));
            
            // Count existing number of sub_fields
            $currSubFieldCount = 0;
            $countedSubFields = [];
            foreach($existingValues as $itemValue)
            {
                if($itemValue->getFieldId() == $fieldId && !in_array($itemValue->getSubFieldId(), $countedSubFields)) {
                    $currSubFieldCount++;
                    $countedSubFields[] = $itemValue->getSubFieldId();
                }
            }

            // Loop over sub_fields in the field
            foreach($subFields as $subFieldId => $subValues)
            {
                if(!is_array($subValues)) $subValues = [$subValues];

                // Count new number of sub_values within this sub_field so we can detect a count drop, in which case we
                // trigger a full write of all sub_values
                $newSubValueCount = count($subValues);
                if($newSubValueCount > 1) {
                    if(end($subValues) == '' && count($subValues) > 1) {
                        array_pop($subValues);
                        $newSubValueCount --;
                    }
                    $this->itemValues[$field->getName()][$subFieldId] = $subValues;
                }

                // Count existing number of sub_values
                $currSubValueCount = 0;
                foreach($existingValues as $itemValue)
                {
                    if($itemValue->getFieldId() == $fieldId && $itemValue->getSubFieldId() == $subFieldId) $currSubValueCount++;
                }

                //* DEBUG
                if($newSubValueCount < $currSubValueCount)
                {
                    $this->logger->debug('__________ subValueCount for fieldId ::'.$fieldId.'^'.$subFieldId.':: dropped from::'.$currSubValueCount.':: to ::'.$newSubValueCount.' -- will write all sub_fields');
                } else {
                    $this->logger->debug('__________ subValueCount for fieldId ::'.$fieldId.'^'.$subFieldId.':: DID NOT DROP -- from::'.$currSubValueCount.':: to ::'.$newSubValueCount);
                }
                // */

                // Check if we need to insert each sub value
                $subValueId=0;
                foreach($subValues as $subValueId => $newValue)
                {
                    $this->logger->debug('__________   subValueId '.$subValueId);

                    // Rich text fields have macro processing performed on input, to be combined
                    // with user-supplied HTML
                    if($field->option('richText'))
                    {
                        $this->logger->debug('********** field is RichText, running macros on input');
                        // Run macro processing on it, normally done on input now
                        $newValue = $this->textMacroService->processMacros($newValue);

                        if(isset($this->itemValues[$field->getName()]))
                        {
                            if(!is_array($this->itemValues[$field->getName()])) {
                                $this->itemValues[$field->getName()] = $newValue;
                            } elseif(!is_array($this->itemValues[$field->getName()][$subFieldId])) {
                                $this->itemValues[$field->getName()][$subFieldId] = $newValue;
                            } else {
                                $this->itemValues[$field->getName()][$subFieldId][$subValueId] = $newValue;
                            }
                        }
                    } else {
                        $this->logger->debug('********** field is NOT RichText');
                    }

                    // Calculate what rows need to be inserted
                    // Fail open -- if we don't decide not to write a row, write it
                    $insert_row = true;

                    // If sub_fields or sub_values count dropped for dimension, write the whole dimension
                    // We only check to see if there are added items here, if there are, we may not write
                    // everything, otherwise we will (so we skip the check)
                    if($newSubValueCount >= $currSubValueCount)
                    {
                        // Look for an existing ItemValues row matching the field_id and sub_value_id (array index)
                        $foundRow = null;
                        foreach($existingValues as $itemValue)
                        {
                            if($itemValue->getFieldId() == $fieldId && $itemValue->getSubFieldId() == $subFieldId && $itemValue->getSubValueId() == $subValueId)
                            {
                                $foundRow = $itemValue;
                                $this->logger->debug('__________     found row for fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);
                                break;
                            }
                        }

                        // Check if this value was copied from the current version to preserve it, in which
                        // case if we reinsert it, it would change the userId on the row to the current user,
                        // even though they did not actually create the value.
                        $alreadyInserted = false;
                        foreach($currentValues as $itemValue)
                        {
                            if($itemValue->getFieldId() == $fieldId && $itemValue->getSubFieldId() == $subFieldId
                                && $itemValue->getSubValueId() == $subValueId
                                && $itemValue->getValue() == $newValue)
                            {
                                $alreadyInserted = true;
                            }
                        }

                        // If the field is a relationship, enum field, or userlist get the label for the value
                        // Old value's label is got after we check foundRow is not NULL
                        $oldValueLabel = '';
                        $newValueLabel = '';
                        if($newValue)
                        {
                            switch($field->getFieldType())
                            {
                                case 'Relationship':
                                    // DEBUG $this->logger->debug(__METHOD__.'::relationship field, get NEW value label for '.$newValue);
                                    $newValueLabel = ': '.$this->itemsRepository->getItem($this->accountId, $newValue, 0, 0)->getTitle();
                                    break;
                                /*case 'Enum':
                                    // DEBUG $this->logger->debug(__METHOD__.'::enum field, get NEW value label for '.$newValue);
                                    $newValueLabel = ': '.$field->getFieldOptions()->options->{$newValue};
                                    break;*/
                                case 'UserList':
                                    // DEBUG $this->logger->debug(__METHOD__.'::userlist field, get NEW value label for '.$newValue);
                                    $newValueLabel = ': '.$this->users->getUser($this->accountId, $newValue)->getName();
                                    break;
                            }
                        }

                        // If field or sub_field was added (we didn't find a row for it), write row, otherwise check if the
                        // field value changed or not to decide
                        if(!is_null($foundRow)) {
                            
                            // Find old labels for relationship, enum, and userlist fields
                            $oldValue = $foundRow->getValue();
                            if($oldValue)
                            {
                                switch($field->getFieldType())
                                {
                                    case 'Relationship':
                                        // DEBUG $this->logger->debug(__METHOD__.'::relationship field, get old value label for '.$oldValue);
                                        $oldValueLabel = ': '.$this->itemsRepository->getItem($this->accountId, $oldValue, 0, 0)->getTitle();
                                        break;
                                    /*case 'Enum':
                                        // DEBUG $this->logger->debug(__METHOD__.'::enum field, get NEW value label for '.$oldValue);
                                        $oldValueLabel = ': '.$field->getFieldOptions()->options->{$oldValue};
                                        break;*/
                                    case 'UserList':
                                        // DEBUG $this->logger->debug(__METHOD__.'::userlist field, get NEW value label for '.$oldValue);
                                        $oldValueLabel = ': '.$this->users->getUser($this->accountId, $oldValue)->getName();
                                        break;
                                }
                            }

                            // If field did not changed, do not write row
                            if($field->option('editable', true) || $foundRow->getCreatedAt() > time() - 15*60)
                            {
                                $this->logger->debug('__________       <b></b>compare "'.$newValue.'"(('.md5($newValue).')) to "'.$foundRow->getValue().'"(('.md5($foundRow->getValue()).')) was posted on '.date('D M j G:i:s T Y', $foundRow->getCreatedAt()));
                                if($newValue === $foundRow->getValue()) {
                                    $this->logger->debug('__________         value did not change for fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);
                                    $insert_row = false;
                                } else {
                                    $this->logger->debug('__________         value DID change for fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);
                                    

                                    // If old value wasn't created by this user, do not write row
                                    if($field->option('editable', true) == false && $foundRow->getUserId() !== $userId)
                                    {
                                        $insert_row = false;
                                        $this->logger->debug('__________         editable=false; skip; value was NOT created by current user for fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);
                                    }

                                    if($insert_row)
                                    {
                                        $only_html_changed = strip_tags($foundRow->getValue()) === strip_tags($newValue);

                                        $old_newlines_changed = trim(preg_replace('/\s+/', ' ', $foundRow->getValue()))
                                                                    === trim(preg_replace('/\s+/', ' ', $newValue));

                                        // If only HTML or newlines changed, do not add to changelog
                                        if($only_html_changed || $old_newlines_changed)
                                        {
                                            $this->logger->debug('__________         only HTML or newlines changes, skipping changelog entry');
                                        } else {
                                            $this->addChangeLog($field->getVisibleToRestrictedUsers(),
                                                                $field->getLabel(), 'modified',
                                                                $foundRow->getValue().$oldValueLabel,
                                                                $newValue.$newValueLabel);
                                        }
                                    }
                                }
                            } else {
                                $this->logger->debug('__________         editable=false; skip; value is too old to change (posted '.date('D M j G:i:s T Y', $foundRow->getCreatedAt()).') for fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);
                                $insert_row = false;
                            }

                            // TODO: Count dimension to update sub_field_count, sub_value_count
                        } else {
                            if($alreadyInserted)
                            {
                                $insert_row = false;
                                $this->logger->debug('_*________         skip; value was copied from current version and so does not need to be re-inserted for fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);
                            } else {
                                $this->logger->debug('__________       did not find row for fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);
                                $this->addChangeLog($field->getVisibleToRestrictedUsers(), $field->getLabel(), 'added', '', $newValue.$newValueLabel);
                            }
                            //if($newValue == '') {
                            //    $this->logger->debug('new value is blank, not inserting fieldId::'.$fieldId.'^'.$subValueId);
                            //    $insert_row = false;
                            //}
                        }
                    } // end of $newSubValueCount >= $currSubValueCount
                    

                    // Insert the row if we need to
                    if($alreadyInserted)
                    {
                        $this->logger->debug('!!!!!!!!!!         fallback skip; value was copied from current version and so does not need to be re-inserted for fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);
                    } else {
                        if($insert_row) {

                            // TODO Add row to insert to newRows array
                            $newRows[] = [$this->accountId, $userId, $this->id, $fieldId,
                                        $newSubFieldCount, $subFieldId,
                                        $newSubValueCount, $subValueId,
                                        
                                        $newValue, $new_ver, $prev_ver];
                            
                            $changeCount++;
                        } else {
                            // DEBUG $this->logger->debug('not inserting fieldId::'.$fieldId.'^'.$subValueId);
                        }
                    }

                    // DEBUG $this->logger->debug('increment subValueId');
                    //$subValueId++;
                }   // $subValues

            }   // $subFields
        }


        // ***** Check for conflicts

        // Check if prev_ver this is based on is the latest, if not, cancel save
        // var_dump([
        //          'ver' => $this->ver,
        //          'prev_ver' => $prev_ver,
        //          'new_ver' => $new_ver]);
        
        if((int)$this->ver != (int)$prev_ver)
        {
            // Ver has changed, so there are potentially conflicts, but we need to find them and
            // if see if changes can be merged or are really conflicting...
            $conflicts = 0;

            // Check for conflicts
            foreach($newRows as $newRowNo => $row)
            {
                $rowConflicts = false;

                list($this->accountId, $userId, $this->id, $fieldId,
                        $newSubFieldCount, $subFieldId,
                        $newSubValueCount, $subValueId,
                        $newValue, $new_ver, $prev_ver) = $row;

                $rowField = null;
                foreach($this->itemType->getFields() as $field)
                {
                    if($fieldId == $field->getId())
                    {
                        $rowField = $field;
                    }
                }

                // Check for conflicting rows --
                //  Rows where:
                //       New value is different from existingValue
                //   AND New value is different from currentValue
                
                // Did anyone ELSE modify the
                // value (e.g. is the existingItemValue from the prev_ver version actually DIFFERENT from the
                // currentItemValue from the current ver?)
                

                // echo '<b>currentValues</b><br>';
                // var_dump($currentValues);
                // echo '<b>existingValues</b><br>';
                // var_dump($existingValues);

                foreach($currentValues as $currentItemValue)
                {
                    if($currentItemValue->getFieldId() == $fieldId && $currentItemValue->getSubFieldId() == $subFieldId &&
                       $currentItemValue->getSubValueId() == $subValueId &&
                       $currentItemValue->getValue() != $newValue)
                    {
                        $conflictingCurrentItemValue = $currentItemValue;
                        $rowConflicts = true;
                        $conflictsExistingValue = false;

                        $this->logger->debug('!!!!!!!!!! Potential conflict found at fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);

                        foreach($existingValues as $existingItemValue)
                        {
                            // Did the user actually modify this value?
                            // (In theory the above logic will only insert newRows items where the user has actually
                            // made a change, but check to be sure)
                            if($existingItemValue->getFieldId() == $fieldId && $existingItemValue->getSubFieldId() == $subFieldId &&
                               $existingItemValue->getSubValueId() == $subValueId &&
                               $existingItemValue->getValue() != $currentItemValue->getValue())
                            {
                                $conflictingExistingItemValue = $existingItemValue;

                                $this->logger->debug('!!!!!!!!!! CONFIRMED conflict found at fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);

                                // Value has also been modified in DB, this is a CONFLICT because we cannot
                                // accept the DB value without user review
                                $conflictsExistingValue = true;
                                
                                $conflicts++;
                                break;
                            }
                        }

                        if(!$conflictsExistingValue)
                        {
                            if($userId == $currentItemValue->getUserId())
                            {
                                // Not a conflict, we're just editing our own row
                                $this->logger->debug('!!!!!!!!!! NOT a conflict, editing own row at fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$subValueId);
                            } else {
                                // No exsting value was found, but it appear someone else has added a row here so we are still in conflict
                                // If this is a multiText field we can resolve this automatically by incrementing subValueId
                                if($rowField->getFieldType() == 'MultiText')
                                {
                                    // subValueId is zero based, count is 1 based

                                    $oldSubValueCount = $newSubValueCount;
                                    $newSubValueCount = $currentItemValue->getSubValueCount()+1;

                                    $oldSubValueId = $subValueId;
                                    $subValueId = $newSubValueCount-1;

                                    // Make sure we aren't inserting a duplicate row by checking
                                    // existing insert records for the same subValueID
                                    foreach($newRows as $newRowCheck)
                                    {
                                        list($checkAccountId, $checkUserId, $checkItemId, $checkFieldId,
                                            $checkNewSubFieldCount, $checkSubFieldId,
                                            $checkNewSubValueCount, $checkSubValueId,
                                            $checkNewValue, $checkNew_ver, $checkPrev_ver) = $newRowCheck;
                                        if($checkItemId == $this->id
                                                && $checkFieldId == $rowField->getId()
                                                && $checkSubFieldId == $subFieldId) {
                                            if($checkSubValueId == $subValueId) {
                                                $subValueId = $checkSubValueId+1;
                                            }
                                        }
                                    }

                                    $newSubValueCount = $subValueId+1;  // +1 because count is 1 based
                                    

                                    $this->logger->debug('CONFLICT RESOLUTION MultiTextField: increment subValueCount from fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$oldSubValueId.'  TO  '.
                                        $fieldId.'*'.$subFieldId.'^'.$subValueId);

                                    // Increment the subValueId past the current version's count
                                    $newRows[$newRowNo][6] = $newSubValueCount;
                                    $newRows[$newRowNo][7] = $subValueId;


                                    // Update existing rows to insert to match new subValueCount
                                    foreach($newRows as $newRowCheckId => $newRowCheck)
                                    {
                                        list($checkAccountId, $checkUserId, $checkItemId, $checkFieldId,
                                            $checkNewSubFieldCount, $checkSubFieldId,
                                            $checkNewSubValueCount, $checkSubValueId,
                                            $checkNewValue, $checkNew_ver, $checkPrev_ver) = $newRowCheck;
                                        if($checkItemId == $this->id
                                                && $checkFieldId == $rowField->getId()
                                                && $checkSubFieldId == $subFieldId) {
                                            $newRows[$newRowCheckId][6] = $newSubValueCount;
                                            $this->logger->debug('CONFLICT RESOLUTION MultiTextField: update row sub value count to match subValueCount '.$newSubValueCount.' at fieldId*subFieldId^subValueId::'.$fieldId.'*'.$subFieldId.'^'.$newRowCheckId);
                                        }
                                    }


                                    // if(!is_array($this->itemValues[$rowField->getName()])) $this->itemValues[$rowField->getName()] = $newValue;
                                    // elseif(!is_array($this->itemValues[$rowField->getName()][$subFieldId])) $this->itemValues[$rowField->getName()][$subFieldId] = $newValue;
                                    // else $this->itemValues[$rowField->getName()][$subFieldId][$subValueId] = $newValue;

                                    array_let($this->itemValues, $rowField->getName(), $subFieldId, $subValueId, $newValue);

                                    // Clear conflict since we were able to resolve it
                                    $rowConflicts = false;
                                }
                            }
                        }
                    }
                }

                if($rowConflicts)
                {
                    if(!isset($this->conflicts[$fieldId])) $this->conflicts[$fieldId] = [];
                    // if(!isset($this->conflicts[$fieldId][$subFieldId])) $this->conflicts[$fieldId][$subFieldId] = [];
                    // if(!isset($this->conflicts[$fieldId][$subFieldId][$subValueId])) $this->conflicts[$fieldId][$subFieldId][$subValueId] = [];
                    $this->conflicts[$fieldId][] = (object)[
                        'conflictingCurrentItemValue' => $conflictingCurrentItemValue,
                        'conflictingExistingItemValue' => $conflictsExistingValue ? $conflictingExistingItemValue : null,
                        'newItemValue' => $row
                    ];
                }
            }


            //var_dump($newRows);
            
            // Load unmodified values from current version of item
            foreach($this->itemType->getFields() as $field)
            {
                foreach($currentValues as $currentItemValue)
                {
                    if($currentItemValue->getFieldId() != $field->getId()) continue;

                    // Is this value modified?
                    $modified = false;
                    foreach($newRows as $row)
                    {
                        list($this->accountId, $userId, $this->id, $fieldId,
                        $newSubFieldCount, $subFieldId,
                        $newSubValueCount, $subValueId,
                        $newValue, $new_ver, $prev_ver) = $row;

                        
                        if($currentItemValue->getFieldId() == $fieldId && $currentItemValue->getSubFieldId() == $subFieldId &&
                           $currentItemValue->getSubValueId() == $subValueId &&
                           $currentItemValue->getValue() != $newValue)
                        {
                            $modified = true;
                            break;
                        }
                    }

                    if(!$modified)
                    {
                        if(!is_array($this->itemValues[$field->getName()])) $this->itemValues[$field->getName()] = $currentItemValue->getValue();
                        elseif(!is_array($this->itemValues[$field->getName()][$currentItemValue->getSubFieldId()])) $this->itemValues[$field->getName()][$currentItemValue->getSubFieldId()] = $currentItemValue->getValue();
                        else $this->itemValues[$field->getName()][$currentItemValue->getSubFieldId()][$currentItemValue->getSubValueId()] = $currentItemValue->getValue();
                    }
                }
            }
            // var_dump($currentValues);
            // var_dump($this->itemValues);

            // If conflicts found...
            if($conflicts > 0)
            {


                $this->logger->debug('******************** CONFLICTS ********************');
                $this->logger->debug(print_r($this->conflicts, true));

                // echo('<b>******************** CONFLICTS ********************</b><br>');
                // \htmlentities(print_r($this->conflicts));
                //exit;

                // Generate diffs
                foreach($this->conflicts as $fieldId => $conflicts)
                {
                    foreach($conflicts as $i => $conflict)
                    {
                        //var_dump($conflict->conflictingCurrentItemValue->getValue());
                        //var_dump($conflict->newItemValue[8]);


                        // $diff = FineDiff::getDiffOpcodes($conflict->conflictingCurrentItemValue->getValue(), $conflict->newItemValue[8]);
                        // var_dump($diff);
                        // $this->conflicts[$fieldId][$i]->txtDiff = FineDiff::renderToTextFromOpcodes($conflict->conflictingCurrentItemValue->getValue(), $diff);
                        // $this->conflicts[$fieldId][$i]->diff = FineDiff::renderDiffToHTMLFromOpcodes($conflict->conflictingCurrentItemValue->getValue(), $diff);

                        $diff = new HtmlDiff($conflict->conflictingCurrentItemValue->getValue(), $conflict->newItemValue[8], true);
                        $this->conflicts[$fieldId][$i]->diff = $diff->outputDiff()->toString();

                        //var_dump($this->conflicts[$fieldId][$i]);
                        //exit;
                    }
                }



                // Return error message to user
                $this->error = \AppBundle\Service\ArionErrors::ITEM_NOT_SAVED_OUT_OF_DATE;
                $this->errorMessage = \AppBundle\Service\ArionErrorMessages::ITEM_NOT_SAVED_OUT_OF_DATE;
                return false;
            }
        }


        
        // If no conflicts found, process newRows array, doing all inserts
        
        foreach($newRows as $row)
        {
            // DEBUG $this->logger->debug('inserting row for fieldId::'.$fieldId.'^'.$subValueId);
            //$accountId, $itemId, $fieldId, $subValueCount, $subValueId,
            //$subValueCount, $subValueId, $value, $ver, $prevVer) 
            
            list($this->accountId, $userId, $this->id, $fieldId,
                        $newSubFieldCount, $subFieldId,
                        $newSubValueCount, $subValueId,
                        $newValue, $new_ver, $prev_ver) = $row;

            
            $result = $this->itemValuesRepository->createItemValue(
                        $this->accountId, $userId, $this->id, $fieldId,
                        $newSubFieldCount, $subFieldId,
                        $newSubValueCount, $subValueId,
                        $newValue, $new_ver, $prev_ver) && $result;
            
            // Create meta object for value to keep in sync
            // When we are no longer inserting into itemValues, just make up
            // the createdAt time here with time()
            $metaObject = (object)[
                'createdAt' => time(),
                'createdBy' => !is_null($currentUser) ? $currentUser->getName() : 'Unknown',
                'createdByUserId' => $userId,
            ];

            $field = $this->getField($fieldId);
            $fieldName = $field->getName();

            /*
            //Not sure this would work correctly with blank values
            //array_set($this->itemValuesMeta, $fieldName, (int)$subFieldId, (int)$subValueId, $metaObject);

            if(!array_key_exists($fieldName, $this->itemValuesMeta))
            {
                $this->itemValuesMeta[$fieldName] = [];
            } if(!is_array($this->itemValuesMeta[$fieldName])) {
                // Previous version we're merging with had only one subvalue, wrap first value in array
                // @TODO: This may not work correctly with sub_values.
                $this->itemValuesMeta[$fieldName] = [[$this->itemValuesMeta[$fieldName]]];
            }

            if(!array_key_exists($subFieldId, $this->itemValuesMeta[$fieldName]))
            {
                $this->itemValuesMeta[$fieldName][(int)$subFieldId] = [];
            }


            $this->itemValuesMeta[$fieldName][(int)$subFieldId][(int)$subValueId] = $metaObject; 
            */
            
            if(!array_key_exists($fieldName, $this->itemValuesMeta))
            {
                $this->itemValuesMeta[$fieldName] = [];
            }

            if(!is_array($this->itemValuesMeta[$fieldName])) $this->itemValuesMeta[$fieldName] = [$this->itemValuesMeta[$fieldName]];
            if(count($this->itemValuesMeta[$fieldName]) > 0) {
                if(!isset($this->itemValuesMeta[$fieldName][0]) || !is_array($this->itemValuesMeta[$fieldName][0])) $this->itemValuesMeta[$fieldName] = [$this->itemValuesMeta[$fieldName]];
            }

            
            if(!array_key_exists($subFieldId, $this->itemValuesMeta[$fieldName]))
            {
                $this->itemValuesMeta[$fieldName][$subFieldId] = [];
            }

        
            $this->itemValuesMeta[$fieldName][$subFieldId][$subValueId] = $metaObject;
        }

        $this->ver = $new_ver;
        $this->title = trim($newTitle);
        $this->changeCount = $changeCount;
        $this->fieldCount = $fieldCount;
        return $result && $this->itemsRepository->saveItem($this);
    }

    public function getChangeCount()
    {
        return $this->changeCount();
    }

    /**
     * Add a row to the change log
     *
     * @param $restricted should this also be added to the restricted log?
     * @param $fieldLabel label for field
     * @param $action what was done to the field (added, updated, etc.)
     * @param $oldValue
     * @param $newValue
     */
    private function addChangeLog($restricted, $fieldLabel, $action, $oldValue, $newValue)
    {
        if($oldValue == '' && $newValue == '') return;

        $row = '<b>'.\htmlentities($fieldLabel).'</b> '.\htmlentities($action).':<br/>'.PHP_EOL;

        if($oldValue) {
            $row .= '<em>Was</em>'.PHP_EOL.
                    '<blockquote>'.
                    $this->encodeValueForChangeLog($oldValue).
                    '</blockquote> '.PHP_EOL.
                    '<em>changed to</em> '.PHP_EOL.'<blockquote>';
        }
        
        $row .= $this->encodeValueForChangeLog($newValue);

        if($oldValue) {
            $row .= '</blockquote>';
        }

        $row .= '<br/><br/>'.PHP_EOL.PHP_EOL;

        $this->changeLog .= $row;

        if($restricted)
        {
            $this->restrictedChangeLog .= $row;
        }
    }

    private function encodeValueForChangeLog($value)
    {
        if(strpos($value, '<p>') !== false or strpos($value, '<div>') !== false 
             or strpos($value, '<blockquote>') !== false) {
            return $this->textMacroService->purify(str_replace('  ', '&nbsp;&nbsp;', $value));
        } else {
            return $this->textMacroService->purify($this->textMacroService->processMacros(str_replace('  ', '&nbsp;&nbsp;', nl2br($value))));
        }
    }

    /**
     * Return nl2br()'d value if the value doesn't already have HTML in it
     */
    private function nl2br($value)
    {
        if(strpos($value, '<') !== false
            && strpos($value, '>') !== false)
        {
            return nl2br($value);
        } else {
            return $value;
        }
    }

    /**
     * Get the change log from last save()
     */
    public function getChangeLog($restricted=true)
    {
        if($restricted) {
            return $this->restrictedChangeLog;
        } else {
            return $this->changeLog;
        }
    }

    public function itemHasConflicts()
    {
        return count($this->conflicts);
    }

    public function getFieldConflicts($fieldId)
    {
        return $this->conflicts[$fieldId];
    }

    public function fieldHasConflicts($fieldId)
    {
        if(isset($this->conflicts[$fieldId]))
            return true;
        else
            return false;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * Get all fields for the item
     *
     * @return array of Field model objects
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get a field for the item
     *
     * @param integer $fieldId
     * @return Field
     */
    public function getField($fieldId)
    {
        if(isset($this->fields[$fieldId]))
        {
            return $this->fields[$fieldId];
        } else {
            throw new \InvalidArgumentException("Invalid fieldid");
        }
    }

    /**
     * Get all item values for rendering
     *
     * @return array of field_name => value mappings
     */
    public function getValues()
    {
        return $this->itemValues;
    }

    /**
     * Check if a value exists
     *
     * @param $name
     * @return boolean
     */
    public function hasValue($name)
    {
        /*dump($this->itemTypeId);
        dump($this->itemType);
        exit;*/
        if(!$this->hasCache('hasvalue.'.$name)) {
            foreach($this->itemType->getFields() as $field) {
                if($field->getName() == $name) {
                    $this->setCache('hasvalue.'.$name, true);
                    break;
                }
            }
        }
        return $this->getCache('hasvalue.'.$name, false);
    }

    /**
     * Get value of a single field
     *
     * @param $name
     * @param $default
     */
    public function getValue($name, $default='')
    {
        if(isset($this->itemValues[$name])) {
            // DEBUG $this->logger->debug(__METHOD__.'::'.$name.'='.json_encode($this->itemValues[$name]));
            return $this->itemValues[$name];
        } else {
            return $default;
        }
    }

    /**
     * Update the value of a field
     *
     * @param $name
     * @param $value
     */
    public function setValue($name, $value)
    {
        // DEBUG $this->logger->debug(__METHOD__.'::'.$name.'='.json_encode($value));
        $this->itemValues[$name] = $value;
    }

    /**
     * Check if a cache item exists
     *
     * @param $name
     * @param $default
     */
    public function hasCache($name)
    {
        return isset($this->cacheValues[$name]);
    }

    /**
     * Get cache item
     *
     * @param $name
     * @param $default
     */
    public function getCache($name, $default='')
    {
        if(isset($this->cacheValues[$name])) {
            return $this->cacheValues[$name];
        } else {
            return $default;
        }
    }

    /**
     * Update a cache value
     *
     * @param $name
     * @param $value
     */
    public function setCache($name, $value)
    {
        $this->cacheValues[$name] = $value;
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
     * Iintended for unit tests
     */
    public function setId($id)
    {
        $this->id = $id;
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
     * Set itemOwnerType
     *
     * @param ItemType $itemOwnerType
     *
     * @return Item
     */
    public function setOwnerItemType(\AppBundle\Entity\ItemType $ownerItemType)
    {
        $this->ownerItemType = $ownerItemType;

        return $this;
    }

    /**
     * Get itemOwnerTypeId
     *
     * @return integer
     */
    public function getOwnerItemType()
    {
        return $this->itemOwnerType;
    }

    /**
     * Set ownerItem
     *
     * @param integer $ownerItem
     *
     * @return Item
     */
    public function setOwnerItem($ownerItem)
    {
        $this->ownerItem = $ownerItem;

        return $this;
    }

    /**
     * Get ownerItem
     *
     * @return Item
     */
    public function getOwnerItem()
    {
        return $this->ownerItem;
    }

    /*
     * Get an array of [ownerItemTypeId, ownerItemId], useful for passing 
     * to service methods, etc. 
     * Owner items are an item from an item type with own_users == true, which
     * is the same as the value of a relationship field on the userItemId specified
     */
    public function getOwnerId()
    {
        if($this->ownerItemType && $this->ownerItem) {
            return [$this->ownerItemType->getId(), $this->ownerItem->getId()];
        } else {
            $this->logger->warning(__METHOD__.'::result not cached on item, calling service');
            
            $itemType = $this->getItemType();
            foreach($itemType->getFields() as $field)
            {
                if($field->getFieldType() == "Relationship" && $field->getFieldItemType()->getOwnUsers() && $this->getValue($field->getName()) != $this->getId()) {
                    $itemId = $this->getValue($field->getName());
                    if($itemId) {
                        $owner = [(int)$field->getFieldItemType()->getId(), (int)$this->getValue($field->getName())];
                        $this->setOwnerItemType($field->getFieldItemType());
                        $this->logger->warning(__METHOD__.'::get item from field '.$field->getName());
                        $this->setOwnerItem($this->itemsRepository->getItem($this->getAccountId(), $itemId, 0, 0, true));
                    } else {
                        $owner = [0,0];
                    }
                    
                    return $owner;
                }
            }
        }
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

    public function getData()
    {
        return $this->itemValues;
    }

    public function setData($data)
    {
        $this->itemValues = $data;
        return $this;
    }

}
