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
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use AppBundle\Fields\Meta\FieldMetaFactory;
use AppBundle\Service\TextMacroService;

/**
 * ItemVersionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ItemVersionRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var ItemTypeRepository
     */
    protected $itemTypes;

    /**
     * @var FieldRepository
     */
    protected $fields;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var FieldMetaFactory
     */
    protected $fieldMetaFactory;

    /**
     * @var AppBundle\Service\SiteService
     */
    protected $site;

    private $cache;

    /**
     * Called by service bindings in services.yml instead of __construct, which is needed by
     * Doctrine.
     */
    public function initService(LoggerInterface $logger,
                                ItemTypeRepository $itemTypes,
                                UserRepository $users,
                                FieldRepository $fields,
                                FieldMetaFactory $fieldMetaFactory)
    {
        $this->logger = $logger;
        $this->itemTypes = $itemTypes;
        $this->users = $users;
        $this->fields = $fields;
        $this->fieldMetaFactory = $fieldMetaFactory;
    }

    public function clearCache()
    {
        $this->cache = [];
    }
    
    /**
     * Create an item version from an Item entity.
     *
     * @param integer $accountId account to create item under
     * @param integer $userId user creating the item
     * @param string $itemTypeName type to store item as
     * @param array of string $item_values extended properties to store for the item
     * @return Item_model
     */
    public function createItemVersion(Item $item)
    {
        // DEBUG $this->logger->debug(__METHOD__.'::params::'.json_encode(['accountId' => $accountId, 'itemTypeName' => $itemTypeName,
        // DEBUG                      'restrictedUserOwnerItemType' => $restrictedUserOwnerItemType, 'restrictedUserOwnerItemId' => $restrictedUserOwnerItemId]));
        
        if(!$accountId || !$itemTypeName || !is_numeric($restrictedUserOwnerItemType) || !is_numeric($restrictedUserOwnerItemId)) 
            throw new \InvalidArgumentException('createitem requires accountId, itemTypeName, restrictedUserOwnerItemType and restrictedUserOwnerItemId');


        if($this->itemTypes->isItemType($accountId, $itemTypeName))
        {
            $itemType = $this->itemTypes->getItemType($accountId, $itemTypeName);

            //$this->logger->debug(__METHOD__.'::itemType::'.print_r($itemType, true));

            $item = new Item();
            $item->initServiceEntity($this->logger, $this, $this->itemValues, $this->users, $this->fieldMetaFactory, $this->textMacroService);
            $item->setAccountId($accountId);
            $item->setItemType($itemType);

            foreach($item_values as $field => $value)
            {
                $item->setValue($field, $value);
            }

            
            // DEBUG $this->logger->debug(__METHOD__.'::check if need to prepopulate item for restricted user');
            if($restrictedUserOwnerItemType && $restrictedUserOwnerItemId)
            {
                $fields = $itemType->getFields();
                //$this->logger->debug(__METHOD__.'::fields::'.print_r($fields, true));
                $prepopulatedCount = 0;
                foreach($fields as $field)
                {
                    if($field->getFieldType() == 'Relationship'
                       && $field->getFieldItemType()->getId() == $restrictedUserOwnerItemType) {
                        $item->setValue($field->getName(), $restrictedUserOwnerItemId);
                        $prepopulatedCount++;
                    }
                }
                if($prepopulatedCount == 0) {
                    $this->logger->error(__METHOD__.'::item will not be visible to user, did not find a relationship field to set to their owner!');
                    throw new \Exception('Sorry, we were unable to create an item with the correct permissions.');
                }
            }
            if($item->save(0, $userId, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId)) {
                $itemId = $item->getId();
                // DEBUG $this->logger->debug(__METHOD__.'::load created item '.$itemId);
                $item = $this->getItem($accountId, $itemId, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);
                return $item;
            }
        }

        return false;
    }

    /**
     * Helper to save item
     */
    public function saveItem(ItemVersion $itemVersion)
    {
        if(!$itemVersion->getId()) {
           $this->getEntityManager()->persist($itemVersion);
        }
        $this->getEntityManager()->flush();
        $this->clearCache();
        return $item->getId();
    }
}

