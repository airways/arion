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

// Psr / General Vendor
use Psr\Log\LoggerInterface;
use Rych\Random\Random;

// Framework
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

// Project
use \AppBundle\Entity\ItemTypeRepository;
use \AppBundle\Entity\ItemRepository;
use \AppBundle\Entity\ItemVersionRepository;
use \AppBundle\Entity\UserRepository;

/**
 * Implements business logic for items and acts as a factory for Domain\Item objects.
 */
class ItemsService {
    use ContainerAwareTrait;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    protected $view;

    /**
     * @var \AppBundle\Entity\ItemTypeRepository
     */
    protected $itemTypes;

    /**
     * @var \AppBundle\Entity\ItemRepository
     */
    protected $items;

    /**
     * @var \AppBundle\Entity\ItemVersionRepository
     */
    protected $itemVersions;

    /**
     * @var \AppBundle\Entity\UserRepository
     */
    protected $users;

    /**
     * @var \AppBundle\Service\SiteService $siteService
     */
    protected $siteService;

    /**
     * @var \AppBundle\Service\AuthService $authService
     */
    protected $authService;

    /**
     * @var Rych\Random\Random
     */
    protected $random;

    /**
     * @var Aws\S3\S3Client
     */
    protected $s3client;

    /**
     * @var \AppBundle\Service\NotificationService
     */
    protected $notificationService;

    /**
     * @var \AppBundle\Service\TextMacrosService
     */
    protected $textMacrosService;


    protected $itemCache = array();

    const REFRESH = true;

    const VIEW_DEFAULT = 'edit';
    const VIEW_TREE = 'tree';

    public function __construct(LoggerInterface $logger,
                                RouterInterface $router,
                                EngineInterface $view,
                                \Rych\Random\Random $random,
                                AuthService $authService, 
                                SiteService $siteService,
                                ItemTypeRepository $itemTypes,
                                ItemRepository $items,
                                ItemVersionRepository $itemVersions,
                                UserRepository $users,
                                \Aws\S3\S3Client $s3client,
                                NotificationService $notificationService,
                                TextMacroService $textMacrosService) {
        $this->logger = $logger;
        $this->router = $router;
        $this->view = $view;
        $this->random = $random;

        $this->authService = $authService;
        $this->siteService = $siteService;
        $this->itemTypes = $itemTypes;
        $this->items = $items;
        $this->itemVersions = $itemVersions;
        $this->users = $users;

        $this->s3client = $s3client;
        $this->notificationService = $notificationService;
        $this->textMacrosService = $textMacrosService;
        
    }

    public function clearCache()
    {
        $this->itemCache = [];
        $this->items->clearCache();
    }

    private function getUrls($itemType, $itemTypePlural, $search, $filters, $sort, $result=false)
    {
        $filterParams = $this->implodeFilterParams($itemType, $filters);
        if(!$result) {
            $result = new stdObject();
        }
        $result->viewUrl = $this->router->generate('items',
                            ['itemType' => $itemTypePlural],
                            UrlGeneratorInterface::ABSOLUTE_URL).
                            '?'.($search ? 'q='.urlencode($search).'&' : '').
                                ($filterParams ? $filterParams : '').
                                ($sort ? 'sort='.$sort.'&' : '').'&viewOnly=1&id=';


        $result->createUrl = rtrim($this->router->generate('items_create',
                        ['itemType' => $itemTypePlural],
                        UrlGeneratorInterface::ABSOLUTE_URL).'?'.$filterParams.'&sort='.$sort, '&');
        return $result;
    }

    /**
     * Get a listing of items for the authenticated account
     *
     * @param string $itemType plural name of item type to list
     * @param string $filters list of filters to apply in the format rule=value/rule2=value2/...
     * @param string $search search for keyword in item values
     * @return ItemsListingResult
     */
    public function listing($itemType, $filters = [], $search="", $sort="", $limit=false, $page=false, $viewOnly=false) {
        //$filters = $this->parseFilters($filters);
        
        if(!array_key_exists('status', $filters)) {
            //$filters['status'] = 'not*closed';
        }

        $this->logger->debug(__METHOD__.'::listing filters '.json_encode($filters).' sort = '.$sort);
        $result = new ItemsListingResult();
        
        if($itemType && $this->itemTypes->isItemType($this->authService->getAccountId(), $itemType))
        {
            $result->itemType = new \AppBundle\Domain\ItemType($this->itemTypes->getItemType($this->authService->getAccountId(), $itemType), $this->logger);
            
            list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $this->authService->getRestrictedUserKey($this->authService->getAccountId(), $this->authService->getUserId());
            

            // Construct blank item that will render the filter select boxes
            $filterModelItem = $this->items->createFilterItem($this->authService->getAccountId(), $itemType);
            $filterModelItem->onAfterCreateFilterItem();
            $result->filterItem = new \AppBundle\Domain\Item($this->logger, $filterModelItem, [], $this->view, $this->authService);
            $result->filterItem->setContainer($this->container);

            // Fill in default filters if they are absent so the template does not complain
            $result->filters = $filters;
            $result->sort = $sort;
            $result->filterFields = [];
            $result->sorterFields = [];
            foreach($result->filterItem->fields() as $field) {
                if($field->isSorter()) {
                    $result->sorterFields[] = $field;
                }
                if($field->isFilter()) {
                    // If the field is a relationship field, and this the current user is a restricted user, only add it
                    // if the item type pointed to by the realtionship is visible to restricted users
                    // Otherwise, for all other filter fields, just add it
                    if($field->fieldType != 'Relationship' 
                       || ($restrictedUserOwnerItemType == 0 && $restrictedUserOwnerItemId == 0)
                       || ($field->fieldType == 'Relationship' && $field->getFieldItemType()->getVisibleToRestrictedUsers()))
                    {
                        $result->filterFields[] = $field;
                    }

                    // Add a blank value to the view's list of filters if it isn't set
                    if(!array_key_exists($field->name, $result->filters)) {
                        $result->filters[$field->name] = '';
                    }

                    // Remove a blank value from the backend filters list or it will look only for items that have
                    // a blank string set, when blank really means ALL
                    if(array_key_exists($field->name, $filters) && $filters[$field->name] == '') {
                        unset($filters[$field->name]);
                    }
                }
            }

            $fieldLabels = [];
            foreach($result->filterItem->fields() as $field)
            {
                $fieldLabels[$field->id] = $field->label;
            }
        
            $result->items = array();
            //$this->logger->debug(__METHOD__.'::call fetchExtraItemTitles');   
            /*$extraTitleValues = $this->itemValues->fetchExtraItemTitles(
                                            $this->authService->getAccountId(), $result->itemType->getId(), $filters, $search,
                                            $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);*/
            $result->extraTitleValues = []; //$extraTitleValues;
            $result->fieldLabels = $fieldLabels;

            $this->logger->debug(__METHOD__.'::call findItems');
            if(!$viewOnly && count($_POST) == 0) {
                foreach($this->items->findItems($this->authService->getAccountId(), $itemType, $filters, $search,
                        $sort, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId, $limit, $page) as $modelItem) {
                    // Make sure we don't create new objects for already existant domain items
                    if(!isset($this->itemCache[$modelItem->getId()])) {     $itemExtraTitleValues = [];
                        //$itemExtraTitleValues = $this->getExtraTitleValuesForItem($extraTitleValues, $fieldLabels, $modelItem->getId());
                        
                        $this->itemCache[$modelItem->getId()] = new \AppBundle\Domain\Item(
                                                        $this->logger, $modelItem, $itemExtraTitleValues,
                                                        $this->view, $this->authService);
                        $this->itemCache[$modelItem->getId()]->setContainer($this->container);
                    }
                    $result->items[] = $this->itemCache[$modelItem->getId()];
                }
            }

            
            $this->getUrls($itemType, $result->itemType->getPluralName(), $search, $filters, $sort, $result);

            

        } else {
            $result->error = ArionErrors::INVALID_ITEM_TYPE;
            $result->error_message = sprintf(ArionErrorMessages::INVALID_ITEM_TYPE, htmlentities($itemType));
        }

        return $result;
    }

    private function getExtraTitleValuesForItem(array $extraTitleValues, array $fieldLabels, $itemId)
    {
        $itemExtraTitleValues = [];
        foreach($extraTitleValues as $value)
        {
            if($value->getItemId() == $itemId)
            {
                $itemExtraTitleValues[$fieldLabels[$value->getFieldId()]] = $value->getValue();
            }
        }
        return $itemExtraTitleValues;
    }
    /**
     * Get an individual item
     *
     * @param integer $itemId item to get
     * @param $refresh ignore cache if set to ItemsService::REFRESH
     * @param $extraTitleValues
     * @param $lightLoad true to skip loading of itemValues
     * @return ItemsGetResult
     */
    public function get($itemId, $refresh=false, $extraTitleValues=[], $lightLoad=false)
    {
        if(!is_numeric($itemId) && !is_null($itemId)) throw new \InvalidArgumentException("itemId must be numeric");

        // Make sure we don't create new objects for already existant domain items
        if($refresh == ItemsService::REFRESH || !isset($this->itemCache[$itemId])) {
            list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $this->authService->getRestrictedUserKey($this->authService->getAccountId(), $this->authService->getUserId());
        try {

            $item = $this->items->getItem($this->authService->getAccountId(), $itemId, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);
        } catch(\InvalidArgumentException $ex) {
            $result = new ItemsGetResult();
            $result->error = ArionErrors::CANNOT_GET_ITEM;
            $result->error_message = $ex->getMessage();
            return $result;
        }

            $this->itemCache[$itemId] = new \AppBundle\Domain\Item($this->logger, $item, [], $this->view, $this->authService);
            $this->itemCache[$itemId]->setContainer($this->container);
        }

        if(!$lightLoad) {
            $this->itemCache[$itemId]->onAfterGetItem();
        }

        $result = new ItemsGetResult();
        $result->item = $this->itemCache[$itemId];
        $result->viewUrl = $this->makeViewUrl($result->item->itemType->getPluralName(), [], '', $result->item->id);
        return $result;
    }

    /**
     * Compose listing() and get() methods into a single result
     *
     * @param $itemType
     * @param $filters
     * @param string $search search for keyword in item values
     * @param $currentItemId
     * @param $viewType determine what view needs to be rendered 
     *  
     * @return ItemsViewResult
     */
    public function view($itemType, $allFilters, $search="", $sort, $currentItemId=0, $viewType=false, $refresh=false, $viewOnly=false)
    {
        if(!is_numeric($currentItemId) && !is_null($currentItemId)) throw new \InvalidArgumentException("currentItemId must be numeric");

        $filters = $this->getFilters($allFilters, $itemType);
        
        $this->logger->debug(__METHOD__.'::view filters '.json_encode($filters).' sort = '.$sort);

        list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $this->authService->getRestrictedUserKey($this->authService->getAccountId(), $this->authService->getUserId());

        $result = new ItemsViewResult();

        $this->logger->debug(__METHOD__.'::call listing for view() ...');
        
        $result->listing = $this->listing($itemType, $filters, $search, $sort, false, false, $viewOnly);

        if($result->listing->error) {
            exit("Listing error in view action: ".$result->listing->error_message);
        }
        
        $this->getUrls($itemType, $result->listing->itemType->getPluralName(), $search, $filters, $sort, $result);

        if($viewType === false)
        {
            $viewType = $result->listing->itemType->option('defaultViewType', ItemsService::VIEW_DEFAULT);
        }
        $result->viewType = $viewType;
        
        if($result->listing->error) {
            $result->error = $result->listing->error;
            $result->error_message = $result->listing->error_message;
            return $result;
        }
        // Get single item views if default view
        if($viewType == ItemsService::VIEW_DEFAULT){
            if(!$currentItemId && count($result->listing->items) > 0)
            {

                $currentItemId = $result->listing->items[0]->id;
                $this->logger->debug('get currentItemId from first listing result: '.$currentItemId);
            } else {
                $this->logger->debug('currentItemId already set or no items in listing result: '.$currentItemId);
            }

            if($currentItemId)
            {
                //$extraTitleValues = $this->getExtraTitleValuesForItem($result->listing->extraTitleValues, 
                //                                                      $result->listing->fieldLabels, $currentItemId);
                
                $result->get = $this->get($currentItemId, $refresh, []);
                if($result->get->error) {
                    $result->error = $result->get->error;
                    $result->error_message = $result->get->error_message;
                }
            }
        }
        // Get 1-level deep descendant details for each result
        if($viewType == ItemsService::VIEW_TREE){
            // get an array of child item types -- item types with a Relationship field that point to this item type
            $childItemTypes = $this->itemTypes->findItemTypesByParent($this->authService->getAccountId(), $result->listing->itemType->getId());

            foreach ($result->listing->items as $parent) {
                // get relationship fields pointing to the current item type
                $fields = $parent->fields;

                foreach($childItemTypes as $childItemType)
                {
                    foreach($childItemType->getFields() as $field)
                    {
                        if($field->getFieldType() == 'Relationship' && $field->getFieldItemType()->getId() == $result->listing->itemType->getId())
                        {
                            //$relatedItems[$childItemType->getId()] = $this->items->findItems($this->authService->getAccountId(), $childItemType->getName(), [$fieldName => $parent->getId()], '', $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);
                            if(!isset($result->childListings[$parent->id])) $result->childListings[$parent->id] = [];
                            $childFilters = $this->getFilters($allFilters, $childItemType->getPluralName());
                            $childFilters[$field->getName()] = $parent->get('id');
                            $childListing = $this->listing($childItemType->getPluralName(), $childFilters, '');
                            $result->childListings[$parent->id][] = $childListing;
                            if(!in_array($childItemType->getPluralName(), $result->childListingFilters)) {
                                if(count($childListing->filterFields) > 0) {
                                    $result->childListingFilters[$childItemType->getPluralName()] = $childListing;
                                }
                            }
                        }
                    }
                    
                }
            }
        }
        //dump($result->childListings);exit;

        

            
        

        return $result;
    }


    /**
     * Parse GET parameters for filters on the specified item type. Keep in mind that
     * symfony changes all periods in GET keys to underscores.
     *
     * @param array $allFilters array of key => value pairs with each key starting with 'filters_'
     * @param string $itemType item type name
     * @return array
     */
    private function getFilters($allFilters, $itemType)
    {
        $filters = [];
        
        foreach($allFilters as $key => $value)
        {
            if(substr($key, 0, strlen('filters_'.$itemType)) == 'filters_'.$itemType)
            {
                $key = explode('_', $key);
                if(count($key) >= 3)
                {
                    // Implode keys past "filters" and itemType, for instance we may have 
                    // a field named assigned_to which needs to be reassembled
                    $filters[implode('_', array_slice($key, 2))] = $value;
                }
            }
        }
        return $filters;
    }

    /**
     * Perform an edit operation on an item
     *
     * @param $itemTypeName
     * @param $filters
     * @param $itemId
     * @param $prev_ver version this is based on, for a diff / changelog
     * @param $data array of normal input data
     * @param $files array of file data
     * @param $cmd array of actions to take on fields/subfields/subvalues
     * @param $sendNotifications bool
     * @return ItemsEditViewResult
     */
    public function edit($itemTypeName, $allFilters, $sort, $itemId, $prev_ver, array $data, array $files, array $cmd, $sendNotifications=true, $overrideUserId=false)
    {
        $userId = $overrideUserId ? $overrideUserId : $this->authService->getUserId();
        
        list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $this->authService->getRestrictedUserKey($this->authService->getAccountId(),
            $userId);
        
        $data = $this->textMacrosService->purify($data);
        
        $result = new ItemsEditResult();

        if(!$itemId)
        {
            $result->error = ArionErrors::ITEM_ID_REQUIRED;
            $result->error_message = ArionErrorMessages::ITEM_ID_REQUIRED;
            return $result;
        }

        if(!is_array($data) || !isset($data[$itemId]) || !is_array($data[$itemId]))
        {
            $result->error = ArionErrors::ITEM_DATA_MISSING;
            $result->error_message = sprintf(ArionErrorMessages::ITEM_DATA_MISSING, $itemId);
            return $result;
        }

        $get = $this->get($itemId);
        if($get->item->update($prev_ver, $userId, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId,
                              $data[$itemId], 
                              array_key_exists($itemId, $files) ? $files[$itemId] : [],
                              array_key_exists($itemId, $cmd) ? $cmd[$itemId] : []))
        {
            $result->message = sprintf(ArionErrorMessages::ITEM_SAVED, $itemId);
        } else {
            if($get->item->getError())
            {
                $result->error = $get->item->getError();
                $result->error_message = $get->item->getErrorMessage();
            } else {
                $result->error = ArionErrors::ITEM_NOT_SAVED;
                $result->error_message = sprintf(ArionErrorMessages::ITEM_NOT_SAVED, $itemId);
            }
        }
        
        // Some item types create users for each record, keep such records in sync
        $syncResult = $this->syncUserRecord($itemTypeName, $itemId);
        // @TODO: Is view default always correct here?
        $editViewResult = new ItemsEditViewResult($this->view($itemTypeName, $allFilters, '', $sort, $itemId, ItemsService::VIEW_DEFAULT, ItemsService::REFRESH));
        $editViewResult->edit = $result;
        $editViewResult->syncUser = $syncResult;
        $editViewResult->prev_ver = $prev_ver;

        // Get changeLog message and send notifications to users
        // One set of changes is for restricted users which excludes fields not marked
        // visible_to_restricted_users, while the other is for normal and admin users
        $restrictedChangeLog = trim($get->item->getChangeLog(true));
        $changeLog = trim($get->item->getChangeLog(false));

        if(!$result->error)
        {
            if(!$sendNotifications)
            {
                $this->logger->debug('***** sendNotifications=false, so no notifications will be sent');
            }
            elseif($changeLog || $restrictedChangeLog)
            {
                $this->logger->debug(__METHOD__.'::viewUrl='.$editViewResult->viewUrl);
                $newViewUrl = explode('?', $editViewResult->viewUrl)[0];
                $this->logger->debug(__METHOD__.'::viewUrl='.$newViewUrl);
                $url = $newViewUrl.'?id='.$get->item->get('id');
                $subject = $get->item->get('itemType')->getLabel().' Updated: #'.$get->item->get('id').' '.$get->item->get('title');
                $header = 'Item updated by '.$this->authService->getUser()->getName().
                            ' on '.date('M d').' at '.date('g:ia').':'.
                            '<br/><br/>'.PHP_EOL;
                $footer = '<br/><br/>'.PHP_EOL.
                            '<a href="'.$url.'">'.$url.'</a>';

                // For restricted users
                if($restrictedChangeLog) $restrictedMessage = $header.$this->textMacrosService->processMacros($restrictedChangeLog).$footer;
                else $restrictedMessage = '';
                
                // For admin and normal users
                if($changeLog) $message = $header.$this->textMacrosService->processMacros($changeLog).$footer;
                else $message = '';

                // Determine who to send notification to
                $users = $this->users->findUsers($this->authService->getAccountId(), 0, 0);
                foreach($users as $user)
                {
                    $send = false;

                    $emailPrefs = $user->getEmailPrefs();

                    if(!$emailPrefs) {
                        switch($user->getUserType()) {
                            case AuthService::ADMIN:
                                $emailPrefs = 'all';
                                break;
                            case AuthService::NORMAL:
                                $emailPrefs = 'assigned';
                                break;
                            case AuthService::RESTRICTED:
                                $emailPrefs = 'all';
                                break;
                        }
                    }

                    $send = true;

                    // If the user only wants email for items they are assigned to, check if they are assigned
                    if($emailPrefs == 'none') {
                        $send = false;
                        $this->logger->debug("User doesn't want mail: ".$user->getDisplayName());
                    }

                    if($emailPrefs == 'assigned') {
                        $isAssigned = false;

                        // If the user is assigned to the item, send them a notification
                        foreach($get->item->get('itemType')->getFields() as $field)
                        {
                            if($field->getFieldType() == 'UserList')
                            {
                                if($user->getId() == $get->item->get($field->getName())) {
                                    $isAssigned = true;
                                    break;
                                }
                            }
                        }

                        $send = $send && $isAssigned;
                    }
                    

                    if($user->getUserType() == AuthService::RESTRICTED) {
                        // If the user belongs to the same owner as the item that was edited, send
                        // them a notification
                        $itemOwnerId = $get->item->getOwnerId();
                        $userOwnerId = $this->authService->getRestrictedUserKey($this->authService->getAccountId(), $user->getId());
                        
                        $this->logger->debug("********** RESTRICTED USER **********");
                        $this->logger->debug($user->getDisplayName());
                        $this->logger->debug(print_r($itemOwnerId, true));
                        $this->logger->debug(print_r($userOwnerId, true));
                        $this->logger->debug('---');

                        if(!$userOwnerId[0] || !$userOwnerId[1] || !$itemOwnerId[0] ||!$itemOwnerId[1]) {
                            // Restricted user or an item without an owner should not generate a notice
                            $send = false;
                        } else {
                            // Check if the owner is the same (e.g. same client record)
                            if($itemOwnerId[0] === $userOwnerId[0] && $itemOwnerId[1] === $userOwnerId[1]) {
                                $send = $send && true;
                            } else {
                                // Not the same owner (client), don't send
                                $send = false;
                            }
                        }

                    }

                    if($send)
                    {
                        $this->logger->debug('SEND MESSAGE TO USER '.$user->getEmail());

                        if($user->getUserType() == AuthService::RESTRICTED)
                        {
                            if($restrictedMessage) {
                                $this->notificationService->sendNotification($user->getEmail(), $subject, $restrictedMessage);
                            }
                        } else {
                            if($message) {
                                $this->notificationService->sendNotification($user->getEmail(), $subject, $message);
                            }
                        }
                    } else {
                        $this->logger->debug('DO NOT SEND MESSAGE TO USER '.$user->getEmail());
                    }
                }
            }
        }

        return $editViewResult;
        
    }

    /**
     * Check to see if the item should have a user record for it, if so, create one or edit an existing one.
     *
     * @param $itemTypeName
     * @param $itemId
     */
    public function syncUserRecord($itemTypeName, $itemId)
    {
        $result = new ItemsSyncUserResult();

        if(!$itemId)
        {
            $result->error = ArionErrors::ITEM_ID_REQUIRED;
            $result->error_message = ArionErrorMessages::ITEM_ID_REQUIRED;
            return $result;
        } else {
            $item = $this->get($itemId)->item;
        }

        $result->syncPerformed = false;

        if($itemTypeName && $this->itemTypes->isItemType($this->authService->getAccountId(), $itemTypeName))
        {
            $itemType = $this->itemTypes->getItemType($this->authService->getAccountId(), $itemTypeName);
            if($itemType->getAreUsers())
            {
                $item = $this->get($itemId)->item;
                $this->logger->debug(__METHOD__.'::creating user for item::'.json_encode($item));
                // What should the user's values be?
                $email = is_array($item->email) ? $item->email[0] : $item->email;
                if(is_array($email)) $email = $email[0];
                if($item->hasValue('username'))
                {
                    $username = $item->username;
                } else if($item->hasValue('first_name') || $item->hasValue('last_name')) {
                    $username = $item->first_name;
                    if($item->hasValue('last_name')) {
                        $username .= '_'.$item->last_name;
                    }
                } else {
                    $username = 'user';
                }
                $username .= '_'.$itemId;
                $username = strtolower($username);
                $result->username = $username;

                $displayName = $item->first_name.' '.$item->last_name;


                // Now check the item's record
                $userRecord = $this->users->getUserByItemId($this->authService->getAccountId(), $itemId);
                if($userRecord)
                {
                    // Found user, update it to look how we want
                    $userRecord->setEmail($email);
                    $userRecord->setUsername($username);
                    $userRecord->setDisplayName($displayName);
                    $result->syncPerformed = $this->users->saveUser($userRecord);
                    $result->newPasswordSet = false;
                } else {
                    // Create a new user
                    $randomPassword = $this->random->getRandomString(32);
                    if($email && $username && $randomPassword && $this->authService->getAccountId() && $itemId)
                    {
                        $result->syncPerformed = $this->authService->register($this->authService->getAccountId(), 
                                                                              $email, $username, $displayName,
                                                                              $randomPassword, 
                                                                              $item, 
                                                                              AuthService::RESTRICTED,
                                                                              AuthService::AUTO_ACTIVATE);

                        if($result->syncPerformed)
                        {
                            $result->newPasswordSet = true;
                            $result->newPassword = $randomPassword;
                        }
                    } else {
                        // $this->logger->debug(__METHOD__.'::ERROR::'.json_encode([
                        //                      'email' => $email, 
                        //                      'username' => $username, 
                        //                      'randomPassword' => $randomPassword,
                        //                      'accountId' => $this->authService->getAccountId(), 
                        //                      'itemId' => $itemId]));
                        $result->error_message .= ' '.sprintf(ArionErrorMessages::USER_SYNC_INCOMPLETE_DATA);
                        $result->error = ArionErrors::USER_SYNC_INCOMPLETE_DATA;
                    }
                }

                if($result->syncPerformed)
                {
                    $result->message .= ' '.sprintf(ArionErrorMessages::USER_SYNC_PERFORMED, $result->username);
                }

                if($result->newPasswordSet)
                {
                    $result->message .= ' '.sprintf(ArionErrorMessages::USER_SYNC_NEW_PASSWORD, $result->newPassword);
                }
            }
            $this->logger->debug(__METHOD__.'::result::'.json_encode($result, true));
        }

        
        return $result;

    }

    /**
     * Create a new item
     *
     * @return ItemsCreateResult
     */
    public function create($itemType, array $allFilters=[], $sort='', array $data=[], $overrideUserId=false)
    {
        $userId = $overrideUserId ? $overrideUserId : $this->authService->getUserId();
        $filters = $this->getFilters($allFilters, $itemType);

        foreach($filters as $key => $value)
        {
            if($key != 'status' && strpos($value, '*') === false) {
                $data[$key] = $value;
            }
        }
        
        list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $this->authService->getRestrictedUserKey($this->authService->getAccountId(), $userId);

        // // Parse default values from the filters array
        // $values = [];
        // foreach($filters as $filter => $value)
        // {
        //     $key = 'filters.'.$itemType.'.';
        //     $n = strlen('filters.'.$itemType.'.');
        //     if(substr($filter, $n) === $key)
        //     {
        //         $values = substr($filter, $n, strlen($filter)-$n);
        //     }
        // }
        $item = $this->items->createItem($this->authService->getAccountId(), $userId, $itemType, $data,
                                         $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);
        //$this->itemCache[$item->id] = container()->get('\app\lib\domain\Item', [$item]);
        $item->onAfterCreateItem();

        $result = new ItemsCreateResult();
        $result->item = $item;
        $filterParams = $this->implodeFilterParams($itemType, $filters);
        $result->viewUrl = $this->makeViewUrl($itemType, $filterParams, $sort, $result->item->getId());
        

        return $result;
    }

    /**
     * Parse filter string into array of key=>values
     *
     * @param string $filters formatted filters matching pattern filter1:value1/filter2:value2 etc.
     * @return array
     */
    public function parseFilters($filters)
    {
        $result = [];
        $pairs = explode('/', $filters);
        foreach($pairs as $pair)
        {
            $filter = explode(':', $pair);
            if(count($filter) == 2)
            {
                $result[$filter[0]] = $filter[1];
            }
        }
        return $result;
    }

    private function implodeFilterParams($itemType, array $filters)
    {
        $filterParams = '';
        foreach($filters as $filter => $value)
        {
            if($value == '') continue;
            $filterParams .= urlencode('filters.'.$itemType.'.'.$filter).'='.urlencode($value).'&';

        }
        return $filterParams;
    }

    public function makeViewUrl($itemTypePlural, $filters, $sort, $id)
    {
        return $this->router->generate('items',
                    ['itemType' => $itemTypePlural],
                    UrlGeneratorInterface::ABSOLUTE_URL).
                    '?'.($filters ? $filters : '').($sort ? 'sort='.$sort.'&' : '').'id='.$id;
    }
}

class ItemsListingResult extends BaseServiceResult {
    /**
     * @var \AppBundle\Domain\ItemType
     */
    public $itemType;

    /**
     * @var array of \AppBundle\Domain\Item
     */
    public $items = array();

    public $extraTitleValues = array();
    public $fieldLabels = array();

    /**
     * @var array
     */
    public $filters = array('status' => 'open');
    public $sort;

    public $filterFields = [];
    public $sorterFields = [];

}

class ItemsGetResult extends BaseServiceResult {
    /**
     * @var \AppBundle\Domain\Item
     */
    public $item;

    public $viewUrl;
}

class ItemsViewResult extends BaseServiceResult {
    public $viewUrl;
    public $createUrl;

    /**
     * @var ItemsListingResult
     */
    public $listing;

    /**
     * @var array of itemId => ItemsListingResult
     */
    public $childListings = [];

    /**
     * First listing result from for each itemType, to be used for filter rendering
     * @var array of itemTypeName => ItemsListingResult
     */
    public $childListingFilters = [];

    /**
     * @var ItemsGetResult
     */
    public $get;
}

class ItemsEditResult extends BaseServiceResult {
    /**
     * @var \AppBundle\Domain\Item
     */
    public $item;

    public $prev_ver = false;
}

class ItemsEditViewResult extends ItemsViewResult {
    public function __construct(ItemsViewResult $view)
    {
        foreach($view as $k => $v) $this->$k = $v;
    }

    /**
     * @var ItemsEditResult
     */
    public $edit;
}

class ItemsCreateResult extends BaseServiceResult {
    public $viewUrl;

    /**
     * @var \AppBundle\Domain\Item
     */
    public $item;
}

class ItemsSyncUserResult extends BaseServiceResult {
    public $syncPerformed;
    public $username;
    public $newPasswordSet;
    public $newPassword;
}
