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

use AppBundle\Entity\UserSetting;
use AppBundle\Entity\UserSettingRepository;
use AppBundle\Entity\UserRepository;

/**
 * Manages and provides access to the logged in user's preferences
 */
class SettingsService {

    /**
     * @var AppBundle\Entity\UserSettingRepository
     */
    protected $userSettings;

    /**
     * @var AppBundle\Entity\UserRepository
     */
    protected $usera;

    protected $accountId = NULL;
    protected $userId = NULL;

    protected $defaultSettings = [
        'displayName' => '',
    ];

    public function __construct(LoggerInterface $logger,
                                AuthService $authService,
                                UserSettingRepository $userSettings,
                                UserRepository $users) {
        $this->logger = $logger;
        $this->authService = $authService;
        $this->accountId = $authService->getAccountId();
        $this->userId = $authService->getUserId();
        $this->userSettings = $userSettings;
        $this->users = $users;
    }

    /**
     * Get a user prefs value
     *
     * @param $key
     * @return string
     */
    public function get($key)
    {
        // TODO: Look up prefs in database
        switch($key) {
            case 'default.item.type':
                return 'tickets';
        }
    }

    /**
     * Get settings for the logged in user, with defaults set from $defaultSettings
     *
     * @return GetUserSettingsResult
     */
    public function getUserSettings()
    {
        $result = new GetUserSettingsResult();
        $result->user = $this->authService->getUser();
        $result->settings = $this->defaultSettings;

        $rows = $this->userSettings->findByUserId($this->userId);
        foreach($rows as $row)
        {
            $result->settings[$row->getKey()] = $row->getValue();
        }

        $result->settings['displayName'] = $result->user->getDisplayName();

        $result->success = true;
        return $result;
    }

    /**
     * Save settings for the logged in user.
     *
     * @param array $settings key => value pairs of settings
     * @param array $changePassword key => value where key is (currentPassword, newPassword, confirmPassword)
     * @return SetUserSettingsResult
     */
    public function setUserSettings(array $settings, array $changePassword)
    {
        $result = new SetUserSettingsResult();

        $getUserSettings = $this->getUserSettings();
        $result->settings = $getUserSettings->settings;
        $result->user = $getUserSettings->user;

        $currentSettings = $this->getUserSettings();
        foreach($settings as $key => $value)
        {
            // Special handling for some keys
            switch($key)
            {
                case 'displayName':
                    $user = $this->authService->getUser();
                    $user->setDisplayName($value);
                    $this->users->saveUser($user);
                    unset($settings[$key]);
                    break;
                default:
                    if(array_key_exists($key, $currentSettings))
                    {
                        if($currentSettings[$key] != $value)
                        {
                            $row = $this->userSettings->findByUserIdAndKey($this->userId, $key);
                            $row->value = $value;
                            $this->userSettings->saveSetting($row);
                        }
                    } else {
                        $row = new UserSetting();
                        $row->setUserId($this->userId);
                        $row->setKey($key);
                        $row->setValue($value);
                        $this->userSettings->saveSetting($row);
                    }
            
            }

            $result->settings[$key] = $value;
        }

        if(count($changePassword) == 3
            && ($changePassword['currentPassword'] != ''
            || $changePassword['newPassword'] != ''
            || $changePassword['confirmPassword'] != ''))
        {
            $user = $this->authService->getUser();
            
            $result->changePassword = $this->authService->changePassword(
                                            $changePassword['currentPassword'],
                                            $changePassword['newPassword'],
                                            $changePassword['confirmPassword']);

            
        }

        $result->success = true;
        $result->message = 'Settings have been saved.';
        return $result;
    }
}

class GetUserSettingsResult extends BaseServiceResult {
    /**
     * Array of key => value pairs for the user's settings
     * @var array
     */
    public $settings = [];

    /**
     * @var AppBundle\Entity\User
     */
    public $user = null;
}

class SetUserSettingsResult extends GetUserSettingsResult {
    /**
     * @var \AppBundle\Service\AuthChangePasswordResult
     */
    public $changePassword;
}
