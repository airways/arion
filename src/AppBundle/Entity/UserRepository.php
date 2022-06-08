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
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\ORM\EntityRepository;

use AppBundle\Service\AuthService;

/**
 * UserRepository
 *
 */
class UserRepository extends EntityRepository implements UserLoaderInterface
{
    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    private $cache = [];

    /**
     * Called by service bindings in services.yml instead of __construct, which is needed by
     * Doctrine.
     */
    public function initService(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function clearCache()
    {
        $this->cache = [];
    }
    
    /**
     * Allows user to user username or email to log in.
     */
    public function loadUserByUsername($username)
    {
        $cacheKey = 'loadUserByUsernamefindFiles:'.md5($username.':');
        if(isset($this->cache[$cacheKey])) return $this->cache[$cacheKey];

        $username = strtolower($username);
        $user = $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $user) {
            $message = sprintf(
                'Unable to find an active admin AppBundle:User object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message);
        }

        $this->cache[$cacheKey] = $user;
        return $user;
    }


    /**
     * Find users from a particular account ID.
     *
     * @param array of User records
     */
    public function findUsers($accountId, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId)
    {
        if(!$accountId) throw new \InvalidArgumentException('findUsers requires accountId');

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
                $itemOwnerId = $user->getUserItem()->getOwnerId();
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

    /**
     * Get a user by ID
     *
     * @param integer $accountId  account to users from under
     * @param integer $userId  user to get
     * @param User
     */
    public function getUser($accountId, $userId)
    {
        if(!$accountId || !$userId) throw new \InvalidArgumentException('getUser requires accountId and userId, got '.
                                                                        json_encode(['accountId' => $accountId,
                                                                                     'userId' => $userId]).
                                                                        ' call stack '.json_encode(debug_backtrace()));
        
        $cacheKey = 'getUser:'.md5($accountId.':'.$userId.':');
        if(isset($this->cache[$cacheKey])) return $this->cache[$cacheKey];

        $this->cache[$cacheKey] = $this->findOneBy(['accountId' => $accountId,
                                                    'id' => $userId]);
        return $this->cache[$cacheKey];
    }

    /**
     * Get a user by email address from any account
     *
     * @param string $emailAddress  Item ID of user to get
     */
    public function getUserByEmailAddress($email)
    {
        if(is_array($email)) $email = $email[0];
        if(is_array($email)) $email = $email[0];
        if(!trim($email)) throw new \InvalidArgumentException('getUserByEmailAddress requires email');

        $cacheKey = 'getUserByEmailAddress:'.md5(json_encode($email).':');
        if(isset($this->cache[$cacheKey])) return $this->cache[$cacheKey];

        $email = strtolower($email);
        $user = $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $email)
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
        
        $this->cache[$cacheKey] = $user;
        return $this->cache[$cacheKey];
    }

    /**
     * Get a user by an Item ID. This is used for users that are created for a particular Item, for instance
     * a Contact item type may require each entry to have a corresponding user to log in and see what items
     * are assigned to them.
     *
     * @param integer $accountId  account to users from under
     * @param integer $itemId  Item ID of user to get
     */
    public function getUserByItemId($accountId, $itemId)
    {
        if(!$accountId || !$itemId) throw new \InvalidArgumentException('getUserByItemId requires accountId and itemId');

        $cacheKey = 'getUserByItemId:'.md5($accountId.':'.$itemId.':');
        if(isset($this->cache[$cacheKey])) return $this->cache[$cacheKey];

        $this->cache[$cacheKey] = $this->findOneBy(['accountId' => $accountId,
                                 'userItem' => $itemId]);

        return $this->cache[$cacheKey];
    }


    /**
     * Set a user's account_id. Only works for users with no account_id currently set
     *
     * @param integer $accountId  account ID to set to user
     * @param integer $userId  user to change
     */
    public function setNewUserAccountId($accountId, $userId)
    {
        $user = $this->findOneBy(['accountId' => null,
                                 'id' => $userId]);
        if(is_null($user->getAccountId()) || !$user->getAccountId())
        {
            $user->setAccountId($accountId);
            return $user->save();
        }
        return false;
    }


    /**
     * Set a user's user_item_id
     *
     * @param integer $accountId  account to users from under
     * @param integer $userId  user to change
     * @param integer $itemId  item ID to set as user's item
     */
    public function setUserItemId($accountId, $userId, $itemId)
    {
        $user = $this->findOneBy(['accountId' => null,
                                  'id' => $userId]);
    
        $user->setUserItemId($itemId);
        return $user->save();
    }

    /**
     * Set a user's user_type
     *
     * @param integer $accountId  account to users from under
     * @param integer $userId  user to change
     * @param string $userType  one of restricted,normal,admin
     */
    public function setUserType($accountId, $userId, $userType)
    {
        $user = $this->findOneBy(['accountId' => null,
                                  'id' => $userId]);
    
        $user->setUserType($userType);
        return $user->save();
    }

    /**
     * Create a new user
     */
    public function createUser($accountId, $email, $username, $displayName,
                           $autoActivated=FALSE, $userType=AuthService::RESTRICTED, \AppBundle\Domain\Item $userItem=NULL)
    {
        $this->logger->debug(__METHOD__.'::params::'.json_encode(['email' => $email, 'username' => $username, 'autoActivated' => $autoActivated, 'userType' => $userType]));
        
        if(!$accountId || !$email || !$username ) 
            throw new \InvalidArgumentException('createUser requires accountId, email, username, userItem must be item domain object');

        $email = strtolower($email);
        $username = strtolower($username);
        $user = new User();
        $user->setAccountId($accountId);
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setDisplayName($displayName);
        $user->setEmailPrefs('');
        if($autoActivated) {
            $user->setIsActive(true);
        }
        $user->setUserType($userType);
        if($userItem) {
            $user->setUserItem($userItem->getModelItem());
        }
        
        return $user;
    }

    /**
     * Helper to save user
     */
    public function saveUser(User $user)
    {
        if(!$user->getId()) {
           $this->getEntityManager()->persist($user);
        }
        $this->getEntityManager()->flush();
        $this->clearCache();
        return $user->getId();
    }
}
