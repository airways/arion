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
use AppBundle\Entity\ItemTypeRepository;
use AppBundle\Entity\FieldRepository;

class ItemTypesService {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var AppBundle\Entity\ItemTypeRepository
     */
    protected $itemTypes;

    /**
     * @var AppBundle\Entity\FieldRepository
     */
    protected $fields;

    /**
     * @var AppBundle\Service\SiteService $siteService
     */
    protected $siteService;

    protected $accountId = NULL;

    protected $itemCache = array();

    const REFRESH = true;

    public function __construct(LoggerInterface $logger, AuthService $authService, ItemTypeRepository $itemTypes,
                                FieldRepository $fields) {
        $this->logger = $logger;
        $this->accountId = $authService->getAccountId();
        $this->itemTypes = $itemTypes;
        $this->fields = $fields;
    }

    /**
     * Create default item types and fields for a new account.
     */
    public function createDefaultItemTypes($accountId)
    {
        //createItemType($accountId, $name, $label, $pluralName, $pluralLabel, $areUsers, $ownUser)
        //createField($accountId, $itemTypeId, $name, $label, $field_type, $inTitle, $fieldOptions)
        $client = $this->itemTypes->createItemType(
            $accountId, 'client', 'Client', 'clients', 'Clients', true, false);
        if($client)
        {
            if(!$this->fields->createField(
                $accountId, $client->id, 'name', 'Name', 'Text', true, ''))
                throw new \Exception('Cannot create default item type field client.name');
        } else {
            throw new \Exception('Cannot create default item type client');
        }

        $contact = $this->itemTypes->createItemType(
            $accountId, 'contact', 'Contact', 'contacts', 'Contacts', true, false);
        if($contact)
        {
            $this->fields->createField(
                $accountId, $contact->id, 'first_name', 'First Name', 'Text', true, '');
            $this->fields->createField(
                $accountId, $contact->id, 'last_name', 'Last Name', 'Text', true, '');
            $this->fields->createField(
                $accountId, $contact->id, 'email_address', 'Email Address', 'MultiText', false, '');
            $this->fields->createField(
                $accountId, $contact->id, 'phone_number', 'Phone Number', 'MultiText', false, '');
            $this->fields->createField(
                $accountId, $contact->id, 'client', 'Client', 'Relationship', false, (object)['item_type_id' => $client->id]);
        } else {
            throw new \Exception('Cannot create default item type contact');
        }


        $task = $this->itemTypes->createItemType(
            $accountId, 'task', 'Task', 'tickets', 'Tickets', true, false);
        if($task)
        {
            $this->fields->createField(
                $accountId, $task->id, 'summary', 'Summary', 'Text', true, '');
            $this->fields->createField(
                $accountId, $task->id, 'client', 'Client', 'Relationship', false, (object)['item_type_id' => $client->id]);
            $this->fields->createField(
                $accountId, $task->id, 'description', 'Description', 'TextArea', false, '');
            $this->fields->createField(
                $accountId, $task->id, 'comments', 'Comments', 'MultiText', false, '{"editable": false}');
        } else {
            throw new \Exception('Cannot create default item type task');
        }
    }
}
