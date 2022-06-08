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

namespace AppBundle\Service;

use Psr\Log\LoggerInterface;
use AppBundle\Service\AuthService;
use AppBundle\Entity\UserRepository;
use AppBundle\Entity\FieldRepository;
use AppBundle\Entity\ItemRepository;
use AppBundle\Entity\ItemValueRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Yaml\Yaml;
use Symfony\Bridge\Doctrine\ManagerRegistry as Doctrine;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

class AuthService {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var AppBundle\EntityUserRepository
     */
    protected $users;

    /**
     * @var AppBundle\Entity\FieldRepository
     */
    protected $fields;

    /**
     * @var AppBundle\EntityItemRepository
     */
    protected $items;

    /**
     * @var TokenStorageInterface
     */
    protected $securityTokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $context;

    /**
     * @var AuthenticationUtils
     */
    protected $authenticationUtils;

    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    /**
     * @var Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected $session;

    /**
     * @var Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;


    protected $accountId = NULL;

    protected $apiLogin = false;
    protected $apiAccountId = NULL;

    protected $itemCache = array();

    protected $badPasswords = [];
    protected $providerKey = 'secured_area';

    const RESTRICTED = 'restricted';
    const NORMAL = 'normal';
    const ADMIN = 'admin';

    const SELF_ACTIVATE = 0;
    const AUTO_ACTIVATE = 100;

    public function __construct(LoggerInterface $logger, 
                                UserRepository $users, UserPasswordEncoderInterface $passwordEncoder,
                                FieldRepository $fields,
                                TokenStorageInterface $securityTokenStorage,
                                AuthorizationCheckerInterface $context,
                                AuthenticationUtils $authenticationUtils,
                                SessionInterface $session,
                                EventDispatcher $eventDispatcher) {
        $this->logger = $logger;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->context = $context;
        $this->users = $users;
        $this->passwordEncoder = $passwordEncoder;
        $this->fields = $fields;
        $this->authenticationUtils = $authenticationUtils;
        $this->session = $session;
        $this->eventDispatcher = $eventDispatcher;
    }

    private function getBadPasswords()
    {
        if(count($this->badPasswords) == 0)
        {
            $file = __DIR__.'/../../../app/config/bad_passwords.yml';
            $badPasswords = Yaml::parse(file_get_contents($file));
            $this->badPasswords = $badPasswords['bad_passwords'];
            if(count($this->badPasswords) == 0)
            {
                throw new \Exception('Cannot load list of bad passwords!');
            }
        }
        return $this->badPasswords;
    }

    public function setApiLogin($accountId, $key)
    {
        // TODO store API keys in database
        // TODO make more secure checks against origin of API requests
        if($key === 'ZCx7iiq7eaqn41dSdRJhV7QfVAezZMA1dLFHnU5tC6U7AG4EE5dwywj52SjeE5yH') {
            $this->apiLogin = true;
            $this->apiAccountId = $accountId;
        } else {
            throw new \Exception("Access denied");
        }
    }

    public function isLoggedIn() {
        if($this->apiLogin) return true;
        return $this->context->isGranted('IS_AUTHENTICATED_FULLY');
    }

    public function getUser() {
        if($this->isLoggedIn()) {
            if($this->apiLogin) {
                return $this->users->getUser($this->apiAccountId, $this->getUserId());
            }
            return $this->securityTokenStorage->getToken()->getUser();
        } else {
            throw new \Exception("Tried to getUser when not logged in, always check if logged in first.");
        }
    }

    public function getAccountId()
    {
        if(!$this->isLoggedIn()) return 0;
        if($this->apiLogin) return $this->apiAccountId;

        $user = $this->getUser();
        if($user)
        {
            $this->accountId = $user->getAccountId();
        }
        return $this->accountId;
    }

    public function getUserId()
    {
        if(!$this->isLoggedIn()) return 0;
        // TODO replace with generic system user ID / first admin found or something
        if($this->apiLogin) return 29;
        $user = $this->getUser();
        if($user) {
            return $user->getId();
        } else {
            return 0;
        }
    }

    public function getUserType()
    {
        if(!$this->isLoggedIn()) return '';
        //if($this->apiLogin) return 'admin';
        $user = $this->getUser();
        if($user) {
            return $user->user_type;
        } else {
            return '';
        }
    }

    public function loginAsUser($username, $password) {
        $this->logger->debug(__METHOD__.'::loginAsUser');

        try {
            $user = $this->users->loadUserByUsername($username);
        } catch(UsernameNotFoundException $ex) {
            return false;
        }

        if($user) {
            if($this->passwordEncoder->isPasswordValid($user, $password, $user->getSalt())) {
                
                $token = new UsernamePasswordToken($user, null, $this->providerKey, $user->getRoles());
                $this->securityTokenStorage->setToken($token);
                $this->session->set('_security_'.$this->providerKey, serialize($token));


                // Fire the login event
                //$event = new InteractiveLoginEvent($this->getRequest(), $token);
                //$this->eventDispatcher->dispatch("security.interactive_login", $event);

                return $user;
            } else return false;
        } else return false;
    }

    public function login($start=false) {
        $result = new AuthLoginResult();
        
        // get the login error if there is one
        $result->error = $this->authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $result->lastUsername = $this->authenticationUtils->getLastUsername();
        
        if($start) {
            // 01050: Issue with existing session somehow preventing a login
            $targetPath = $this->session->get('_security.'.$this->providerKey.'.target_path');
            $this->logger->debug(__METHOD__.'::Terminate old session '.$this->session->getId());
            $this->logger->debug(__METHOD__.'::TargetPath was  '.$targetPath);
            $this->session->invalidate();
            $this->session->set('_security.'.$this->providerKey.'.target_path', $targetPath);
        }

        
        return $result;
    }

    public function logout() {
        $this->securityTokenStorage->setToken(null);
        $this->session->invalidate();

    }

    public function register($accountId, $email, $username, $displayName, $plainPassword, \AppBundle\Domain\Item $userItem=NULL,
                             $accountType=AuthService::RESTRICTED, $activationMode=AuthService::SELF_ACTIVATE) {
        if(is_array($email)) $email = $email[0];
        if(is_array($email)) $email = $email[0];
        $this->logger->debug(__METHOD__.'::params::'.json_encode(
                            ['accountId' => $accountId, 'email' => $email, 'username' => $username, 
                            'plainPassword len' => strlen($plainPassword),
                            $userItem ? spl_object_hash($userItem) : 'NULL',
                            'accountType' => $accountType, 'activationMode' => $activationMode]));

        if($this->users->getUserByEmailAddress($email))
        {
            $this->logger->debug(__METHOD__.'::user already exists '.$email);
            return false;
        } else {
            $this->logger->debug(__METHOD__.'::user does not exist '.$email);
            
            $result = $this->users->createUser($accountId, $email, $username, $displayName,
                                                    $activationMode == AuthService::AUTO_ACTIVATE ? true : false,
                                                    AuthService::RESTRICTED,
                                                    $userItem);
            $password = $this->passwordEncoder->encodePassword($result, $plainPassword);
            $result->setPassword($password);
            $this->users->saveUser($result);

            return $result;
        }
    }

    /**
     * Change the currently logged in user's password
     *
     * @param string $currentPassword
     * @param string $newPassword
     * @param string $confirmPassword
     * @return AuthChangePasswordResult
     */
    public function changePassword($currentPassword, $newPassword, $confirmPassword)
    {
        $user = $this->getUser();
        $result = new AuthChangePasswordResult();

        if(!$this->passwordEncoder->isPasswordValid($user, $currentPassword))
        {
            $result->error = 1;
            $result->message = 'Cannot change password: your current password is not correct.';
            return $result;
        }

        $requirements = [
            '/[a-z]/' => 'It does not contain a lowercase letter.',
            '/[A-Z]/' => 'It does not contain an uppercase letter.',
            '/[0-9]/' => 'It does not contain a number.',
            '/[a-z]/' => 'It does not contain a lowercase letter.',
            '/[-!@#$%^&*()_+|~=`{}\[\]:";\'<>?,.\/]/' => 'It does not contain a valid symbol.',
            '/^[^\s]+$/' => 'It contains a whitespace character.',
        ];

        $newPassword = trim($newPassword);
        $confirmPassword = trim($confirmPassword);

        $pw = $newPassword;
        if(strlen($pw) < 8)
        {
            $result->error = 2;
            $result->message = 'Cannot change password: your new password is not at least 8 characters long.';
            return $result;
        }

        if(in_array(strtolower($pw), $this->getBadPasswords(), true))
        {
            $result->error = 2;
            $result->message = 'Cannot change password: your new password is a commonly used password.';
            return $result;
        }

        foreach($requirements as $regex => $message)
        {
            if(!preg_match($regex, $pw))
            {
                $result->error = 2;
                $result->message = 'Cannot change password: your password does not meet the requirements below. '.$message;
                return $result;
            }
        }

        if($pw !== $confirmPassword)
        {
            $result->error = 3;
            $result->message = 'Cannot change password: your new password does not match the password confirmation.';
            return $result;
        }

        
        // Set the password
        $password = $this->passwordEncoder->encodePassword($user, $pw);
        $user->setPassword($password);
        $this->users->saveUser($user);

        $result->message = 'Password changed';
        return $result;
    }

    /**
     * Force a user's password to be changed, useful and used only in unit tests (all other 
     * change password operations must use changePassword instead).
     */
    public function forceChangePassword($user, $pw)
    {
        if(php_sapi_name() != 'cli' || getenv('SYMFONY_ENV')) throw new \Exception(__METHOD__.' cannot be called outside of a unit test');

        $password = $this->passwordEncoder->encodePassword($user, $pw);
        $user->setPassword($password);
        return $this->users->saveUser($user);
    }


    /**
     * If this is a restricted user's session, return their owner item type and owner item id.
     *
     * @param integer $accountId  account to users from under
     * @param integer $userId  user to find key for
     * @return array of integer [$itemTypeId, $userItemId] if restricted, [0,0] if not
     */
    public function getRestrictedUserKey($accountId=0, $userId=0)
    {
        if(!$accountId) $accountId = $this->getAccountId();
        if(!$userId) $userId = $this->getUserId();

        //$this->logger->debug(__METHOD__.'::'.__FILE__.':'.__LINE__.':: call getUser '.json_encode(['accountId' => $accountId, 'userId' => $userId]));
        $user = $this->users->getUser($accountId, $userId);
        if($user->getUserType() != AuthService::NORMAL 
           && $user->getUserType() != AuthService::ADMIN)
        {
            // User Item ID which will be owned by the owner type
            $userItemId = $user->getUserItem()->getId();

            // Get the item that owns the user's item (this will be a client item ID, for instance)
            list($ownerTypeId, $ownerItemId) = $user->getUserItem()->getOwnerId();

            if($ownerTypeId && $ownerItemId)
            {
                return [(int)$ownerTypeId, (int)$ownerItemId];
            } else {
                throw new \Exception('Restricted user does not have an owner!');
            }
        } else {
            // Not a restricted user, return 0s
            return [0,0];
        }
    }

    public function isRestrictedUser($accountId=0, $userId=0)
    {
        $restrictedUserKey = $this->getRestrictedUserKey($accountId, $userId);
        return $restrictedUserKey[0] || $restrictedUserKey[1];
    }
}

class AuthLoginResult extends BaseServiceResult {
    public $lastUsername;
}

class AuthChangePasswordResult extends BaseServiceResult {
}
