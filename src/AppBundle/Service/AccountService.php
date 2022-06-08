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
use AppBundle\Entity\AccountRepository;

class AccountService {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \app\tables\Users
     */
    protected $accounts;

    /**
     * @var \app\lib\SiteService $siteService
     */
    protected $siteService;

    protected $accountId = NULL;

    protected $itemCache = array();

    const REFRESH = true;

    public function __construct(LoggerInterface $logger,
                                AccountRepository $accounts,
                                SiteService $siteService,
                                ItemTypesService $itemTypesService,
                                AuthService $authService) {
        $this->logger = $logger;
        $this->accountId = $authService->getAccountId();
        $this->accounts = $accounts;
        $this->siteService = $siteService;
        $this->itemTypesService = $itemTypesService;
    }

    /**
     * Create a new account
     *
     * @return AccountsCreateResult
     */
    public function create($accountName)
    {
    	$result = new AccountsCreateResult();
        $account = $this->accounts->createAccount($accountName);
        if($account)
        {
        	$result->account = container()->get('\app\lib\domain\Account', [$account]);

            // Create default item types
            // TODO log user in and don't pass accountId to the service, it should never require one and
            // should use the session instead
            $this->itemTypesService->createDefaultItemTypes($result->account->id);
        } else {
        	$result->error = ArionErrors::CANNOT_CREATE_ACCOUNT;
        	$result->error_message = ArionErrorMessages::CANNOT_CREATE_ACCOUNT;
        	
        }
        return $result;
    }

}

class AccountsCreateResult extends BaseServiceResult {
    /**
     * @var \app\lib\domain\Account
     */
    public $account;
}
