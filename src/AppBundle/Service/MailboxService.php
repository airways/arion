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

use AppBundle\Service\AuthService;
use AppBundle\Entity\ItemTypeRepository;
use AppBundle\Entity\MailboxMessageRepository;
use AppBundle\Entity\MailboxRepository;

class MailboxService {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \app\tables\ItemTypes
     */
    protected $itemTypes;

    /**
     * @var \AppBundle\Entity\MailboxRepository
     */
    protected $mailboxes;


    public function __construct(\Psr\Log\LoggerInterface $logger, AuthService $authService, 
                                ItemTypeRepository $itemTypes, MailboxRepository $mailboxes,
                                MailboxMessageRepository $messages) {
        $this->logger = $logger;
        $this->itemTypes = $itemTypes;
        $this->mailboxes = $mailboxes;
        $this->authService = $authService;
    }

    public function findMailboxes()
    {
        $result = new MailboxFindMailboxesResult();
        $result->mailboxes = $this->mailboxes->findByAccountId($this->authService->getAccountId());
        if(count($result->mailboxes) == 0)
        {
            $result->error = true;
            $result->message = 'No mailboxes found for account '.$authService->getAccountId();
        }
        return $result;
    }

    /**
     * Get a listing of mailbox messages for the current user
     *
     * @param array of string $filters list of filters to apply in the format rule:value
     *          valid filters: 
     *              - box -- a mailbox ID
     * @returns array
     */
    public function message_list($filters = array()) {
        $result = new Mailbox_message_list_result();

        $result->mailbox_messages = $this->messages->findMessages($authService->getAccountId(),
            $authService->getUserId(), $filters);

        return $result;
    }

    /**
     * Get an individual message
     *
     * @param integer $mailbox_id unique ID for mailbox
     * @param integer $message_id unique ID for mailbox message
     * @returns Mailbox_message_model
     */
    public function get($mailbox_id, $message_id)
    {
        return $this->messages->getMessage($authService->getAccountId(), $authService->getUserId(),
            $mailbox_id, $message_id);
    }
}

class MailboxFindMailboxesResult extends BaseServiceResult {
    public $mailboxes;
    public $mailbox_messages = array();
    public $message = '';
}

class Mailbox_message_list_result extends BaseServiceResult {
    public $mailbox_messages = array();
    public $message = '';
}
