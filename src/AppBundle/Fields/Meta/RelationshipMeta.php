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

namespace AppBundle\Fields\Meta;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;;

use AppBundle\Entity\Field;
use AppBundle\Entity\ItemRepository;
use AppBundle\Entity\ItemTypeRepository;
use AppBundle\Service\ArionSession;
use AppBundle\Service\AuthService;


class RelationshipMeta extends BaseFieldMeta implements IFieldMeta {

    protected $cache = [];
    
    /**
     * Called by controller to load data needed for the corresponding AppBundle\Fields\*Field->render()
     *
     * @param $field the field to load data for
     * @param $item the item to load data for
     */
    public function load(Field $field) {
        $this->field = $field;

        $this->cache['items'] = [];
        $relatedItemTypeId = $this->field->getFieldItemType()->getId();

        list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $this->authService->getRestrictedUserKey($this->authService->getAccountId(), $this->authService->getUserId());
        
        //$this->logger->debug(__METHOD__.'::call findItems');
        $items = $this->items->findItems($this->field->getAccountId(), $relatedItemTypeId, [], "", "",
                                         $restrictedUserOwnerItemType, $restrictedUserOwnerItemId, false, false);
        if($restrictedUserOwnerItemType || $restrictedUserOwnerItemId)
        {
            // If this is the user's owner type, preserve the owner option even if they could not normally see it
            if($restrictedUserOwnerItemType == $this->field->getFieldItemType()->getId()) {

                $ownerItem = $this->items->getItem($this->field->getAccountId(), $restrictedUserOwnerItemId, 0, 0);
                $this->cache['items'][$ownerItem->getId()] = $ownerItem->getTitle().' <Current>';
            } else {
                // If the item type contains a relationship field that has the user's owner set, preserve those values too
                foreach($this->field->getFieldItemType()->getFields() as $field)
                {
                    if($field->getFieldType() == 'Relationship' && $field->getFieldItemType()->getId() == $restrictedUserOwnerItemType)
                    {
                        //$this->logger->debug(__METHOD__.'::call findItems');
                        $items = $this->items->findItems($this->field->getAccountId(), $this->field->getFieldItemType()->getId(), 
                                                            [], "", [], $restrictedUserOwnerItemType, $restrictedUserOwnerItemId, false, false);
                        foreach($items as $item)
                        {
                            $this->cache['items'][$item->getId()] = $item->getTitle();
                        }
                    }
                }

            }
        }

        foreach($items as $item)
        {
            if($relatedItemTypeId && (int)$item->getItemType()->getId() != (int)$relatedItemTypeId) {
                $this->logger->debug(__METHOD__.'::ERROR - item type '.$relatedItemTypeId.' expected but got at least one item of type '.$item->getItemType()->getId());
                throw new \Exception('Invalid item type ID detected');
            }
            $this->cache['items'][$item->getId()] = $item->getTitle();
        }

        //$this->logger->debug(__METHOD__.'::complete');
    }

    /**
     * Get the item options
     */
    public function getItemOptions()
    {
        if(isset($this->cache['items']))
        {
            return $this->cache['items'];
        } else {
            return [];
        }
    }
}
