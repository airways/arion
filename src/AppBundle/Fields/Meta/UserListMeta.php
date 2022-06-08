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

use AppBundle\Entity\Field;
use AppBundle\Entity\UserRepository;
use AppBundle\Service\ArionSession;
use AppBundle\Service\AuthService;

class UserListMeta extends BaseFieldMeta implements IFieldMeta {

    /**
     * Called by controller to load data needed for the corresponding \lib\forms\fields\*Field->render()
     *
     * @param $field the field to load data for
     * @param $item the item to load data for
     */
    public function load(Field $field) {
        $this->field = $field;

        if(array_key_exists('users', $this->cache)) return;

        //$this->logger->debug(__METHOD__.'::getRestrictedUserKey');
        list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $this->authService->getRestrictedUserKey($this->authService->getAccountId(), $this->authService->getUserId());

        //$this->logger->debug(__METHOD__.'::findUsers');
        $users = $this->users->findUsers($this->field->getAccountId(), $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);
        $this->cache['users'] = [];
        foreach($users as $user)
        {
            //$this->logger->debug(__METHOD__.'::'.$user->getId());

            $suffix = '';
            // Check if the user's itemtype specifies a tag to add to usernames
            if(!is_null($user->getUserItem()) && ($userTag = $user->getUserItem()->getItemType()->option('userTag')) !== false)
            {
                // Only add brackets around it if it's not also a blank string
                if($userTag) {
                    $suffix = ' <'.$userTag.'>';
                }
            } else {
                if($user->getUserType() == 'restricted')
                {
                
                    // Find what item owns the user's item
                    //$this->logger->debug(__METHOD__.'::getOwnerId');
                    //$itemOwnerId = $user->getUserItem()->getOwnerId();
                    $itemOwnerId = $this->items->getUserItem($user)->getOwnerId();
                    
                    //$this->logger->debug(__METHOD__.'::getOwnerId='.json_encode($itemOwnerId));
                    
                    // Get the owner item -- we use ignorePermissions, very dangerous but we are only reading the owner of
                    // an item which is already filtered by owner if the current user is restricted
                    if($itemOwnerId[1])
                    {
                        $ownerItem = $this->items->getItem($this->field->getAccountId(), $itemOwnerId[1],
                                                       $restrictedUserOwnerItemType, $restrictedUserOwnerItemId,
                                                       /* ignorePermissions */true);

                        $suffix = ' <'.$ownerItem->getTitle().'>';
                    }
                }
            }
            $this->cache['users'][$user->getId()] = $user->getName().$suffix;
        }
        //$this->logger->debug(__METHOD__.'::complete');
    }

    /**
     * Get the user options for this field
     */
    public function getUsers()
    {
        if(isset($this->cache['users']))
        {
            return $this->cache['users'];
        } else {
            return [];
        }
    }
}
