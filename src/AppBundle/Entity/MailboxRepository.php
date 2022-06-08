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


/**
 * MailboxRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MailboxRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * Find mailboxes from a particular account and user ID
     *
     * @param $accountId
     * @param userId
     * @return array of Mailbox records
     */
    public function findMailboxes($accountId, $userId)
    {
        if(!$accountId) throw new \InvalidArgumentException('findMailboxes requires accountId');
        if(!$userId) throw new \InvalidArgumentException('findMailboxes requires userId');

        $cacheKey = 'findUsers:'.md5($accountId.':'.$restrictedUserOwnerItemType.':'.$restrictedUserOwnerItemId.':');
        if(isset($this->cache[$cacheKey])) return $this->cache[$cacheKey];

        $users = $this->findBy(['accountId' => $accountId]);
        $result = [];
        
        foreach($users as $user)
        {
            // If the user we found is itself a restricted user, only list it if it comes
            // from the same owner as the current restricted user
            if($user->getUserType() == AuthService::RESTRICTED && ($restrictedUserOwnerItemType || $restrictedUserOwnerItemId))
            {
                $itemOwnerId = $this->itemValues->getOwnerId($accountId, $user->getUserItem()->getId());
                if($itemOwnerId[0] == $restrictedUserOwnerItemType 
                     && $itemOwnerId[1] == $restrictedUserOwnerItemId) {
                    $result[] = $user;
                }
            } else {
                $result[] = $user;
            }
        }

        $this->cache[$cacheKey] = $result;        
        return $result;
        
    }
}