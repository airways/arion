<?php

/**
 * Basic API interface for timesheets and other apps.
 *
 * @package ArionCRM
 * @author Isaac Raway <iraway@metasushi [dot] com>
 * @author Antoinette Smith <asmith@metasushi [dot] com>
 * @link http://arioncrm.com/
 * @copyright (c)2015-2022. MetaSushi, LLC. All rights reserved. Your use of this software in any way indicates agreement
 * to the software license available currenty at http://arioncrm.com/ 
 * This open source edition is released under GPL 3.0. available at https://www.gnu.org/licenses/gpl-3.0.en.html
 */

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use AppBundle\Service\ItemsService;
use AppBundle\Service\AuthService;

/** @Route("/api/v1", service="app.api_controller") */
class Api extends BaseController {

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    protected $view;
    
    /**
     * @var \AppBundle\Service\ItemsService
     */
    protected $itemsService;

    /**
     * @var \AppBundle\Service\SiteService $siteService
     */
    protected $siteService;

    /**
     * @var \AppBundle\Service\SettingsService $settingsService
     */
    protected $settingsService;
    
    /**
     * @var \Symfony\Component\Routing\AuthService
     */
    protected $authService;


    public function __construct(LoggerInterface $logger, AuthService $authService, ItemsService $itemsService)
    {
        $this->logger = $logger;
        $this->authService = $authService;
        $this->itemsService = $itemsService;
    }

    /**
     * Log in as an API user
     *
     * @Route("/login", name="api_login")
     */
    public function loginAction(Request $request)
    {
        try
        {
            $username = '';
            $password = '';
            $result = (object)['success' => false,
                               'error' => false,
                               'userId' => 0];

            if($request->getMethod() == 'POST')
            {
                $username = $request->request->get('username');
                $password = $request->request->get('password');
            }

            if($username && $password)
            {
                $this->logger->debug('API login request '.$username.' '.$password);

                $user = $this->authService->loginAsUser($username, $password);

                if($user)
                {
                    $result->success = true;
                    $result->userId = $user->getId();
                } else {
                    $result->error = 'Invalid login';
                }
            } else {
                $result->error = 'Invalid request, must supply username and password via POST';
            }

        } catch(\Exception $ex) {
            $result->error = explode('call stack ',$ex->getMessage())[0];
        }
        echo json_encode($result, JSON_PRETTY_PRINT).PHP_EOL;
        exit;   
    }

    /**
     * Return a list of items of a particular type assigned to the given user
     * @param $itemType
     * @param $id -- get param
     *
     * @Route("/assignedItems/{itemType}/{userId}", name="api_assignedItems")
     * @Method("GET")
     */
    public function assignedItemsAction(Request $request, $itemType, $userId)
    {
        $this->logger->debug('API assignedItemsAction request '.$itemType.' '.$userId);
        $result = (object)['success' => false,
                               'error' => false,
                               'items' => []
                               ];
        try {
            //public function view($itemType, $allFilters, $search="", $sort, $currentItemId=0, $viewType=false, $refresh=false)
            $serviceResult = $this->itemsService->view($itemType,
                ['filters_tickets_assigned_to' => $userId],
                "",
                "",
                0,
                ItemsService::VIEW_DEFAULT);
            
            $this->logger->debug('got '.count($serviceResult->listing->items).' items');

            foreach($serviceResult->listing->items as $i => $item)
            {
                $item->onAfterGetItem();

                $this->logger->debug('API item '.$i.'='.json_encode(['id' => $item->id, 'title' => $item->title, 'assignedTo' => $item->assignedTo]));
                if(!$item->title) continue;
                if($item->client) {
                    $client = $this->itemsService->get((int)$item->client)->item->title;
                } else {
                    $client = 'ADMIN';
                }

                $result->items[$i] = (object)['itemId' => $item->id,
                                     'client' => $client,
                                     'title' => $item->title,
                                    ];
            }

            $result->success = true;

        } catch(\Exception $ex) {
            $result->error = explode('call stack ',$ex->getMessage())[0].' Make sure user is logged in.';
        }
        echo json_encode($result, JSON_PRETTY_PRINT).PHP_EOL;
        exit;      
        
    }

    

}
