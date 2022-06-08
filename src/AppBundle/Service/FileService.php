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
use \AppBundle\Entity\UserRepository;

/**
 * Implements business logic for files
 */
class FileService {
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
     * @var \AppBundle\Service\NotificationService $notificationService
     */
    protected $notificationService;

    protected $accountId = NULL;
    protected $userId = NULL;

    protected $itemCache = array();

    const REFRESH = true;

    public function __construct(LoggerInterface $logger,
                                AuthService $authService,
                                ItemRepository $items
                                /*RouterInterface $router,
                                EngineInterface $view,
                                \Rych\Random\Random $random,
                                
                                SiteService $siteService,
                                ItemTypeRepository $itemTypes,
                                UserRepository $users,
                                \Aws\S3\S3Client $s3client,
                                NotificationService $notificationService*/) {
        $this->logger = $logger;
        $this->authService = $authService;
        $this->accountId = $authService->getAccountId();
        $this->userId = $authService->getUserId();
        $this->items = $items;

        /*
        $this->router = $router;
        $this->view = $view;
        $this->random = $random;

        $this->siteService = $siteService;
        $this->itemTypes = $itemTypes;
        
        
        $this->users = $users;

        $this->s3client = $s3client;
        $this->notificationService = $notificationService;
        */
        
    }

    public function listing()
    {
        $result = new FilesListingResult();

        // Get item values containing files
        list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $this->authService->getRestrictedUserKey($this->accountId, $this->userId);
        $rawFiles = $this->items->findFiles($this->accountId, $restrictedUserOwnerItemType, $restrictedUserOwnerItemId);

        // Find items for the listed files
        $itemIds = [];
        foreach($rawFiles as $rawFile)
        {
            if(!in_array($rawFile->getItemId(), $itemIds)) $itemIds[] = $rawFile->getItemId();
        }

        // Fetch item titles
        if(count($itemIds) > 0)
        {
            $itemTitles = $this->items->fetchItemTitles($this->accountId, $itemIds);

            // Wrap each item value row in a File domain object
            foreach($rawFiles as $rawFile)
            {
                $result->files[] = new \AppBundle\Domain\File($this->logger, 
                                                              $rawFile, $itemTitles[$rawFile->getItemId()]);
            }
        }

        return $result;
    }
}

class FilesListingResult extends BaseServiceResult {

    /**
     * @var array of \AppBundle\Domain\File
     */
    public $files = array();

    /**
     * @var array
     */
    public $filters = array('status' => 'open');
}
