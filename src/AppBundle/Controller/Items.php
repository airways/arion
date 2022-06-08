<?php

/**
 * Main Items controller to provide listing and detail views of items of any Item Type.
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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use AppBundle\Service\ItemsService;
use AppBundle\Service\SiteService;
use AppBundle\Service\SettingsService;

/** @Route("/items", service="app.items_controller") */
class Items extends BaseController {

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
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
     */
    protected $session;

    public function __construct(LoggerInterface $logger, RouterInterface $router, EngineInterface $view,
                                ItemsService $itemsService,
                                SiteService $siteService, SettingsService $settingsService,
                                SessionInterface $session)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->view = $view;
        $this->itemsService = $itemsService;
        $this->siteService = $siteService;
        $this->settingsService = $settingsService;
        $this->session = $session;
    }


    /**
     * Create a new item
     *
     * @param itemType
     *
     * @Route("/{itemType}/create", name="items_create")
     */
    public function createAction(Request $request, $itemType)
    {
        $allFilters = $this->getFiltersForAllTypes($request);
        $sort = $request->query->get('sort');
        $result = $this->itemsService->create($itemType, $allFilters, $sort);
        if(($res = $this->checkResult($request, $result)) !== 0) return $res;
        
        //return $this->response->redirect($this->siteService->url('items/'.$itemType).'?id='.$result->item->id);
        return new RedirectResponse($result->viewUrl);
    }

    /**
     * Display a listing and item for a particular item type, possibly with filters
     * applied to the listing.
     *
     * Example routes:
     * /items/tasks/                       # Show entire tasks list for current account
     * /items/tasks/?id=100                # Load item 100 with the task list
     * /items/tasks/assigned:me/           # Only show items assigned to the current user
     * /items/tasks/assigned:me/?id=100/   # Only show items assigned to the current user,
     *                                     # with detail view of item 100
     * /items/tasks/assigned:me/due:today/ # Only show items assigned to the current user,
     *                                     # and due on the current date
     *
     * @param $itemType
     * @param $filters -- get param
     * @param $id -- get param
     *
     * @Route("/{itemType}", name="items")
     * @Route("/{itemType}/{filters}", name="items_filtered")
     * @Method("GET")
     */
    public function viewAction(Request $request, $itemType)
    {
        $itemId = $request->query->get('id');
        $search = $request->query->get('q');
        $viewType = $request->query->get('view');
        $viewOnly = $request->query->get('viewOnly');
        if(!in_array($viewType, [ItemsService::VIEW_DEFAULT, ItemsService::VIEW_TREE])) $viewType = false;
        $allFilters = $this->getFiltersForAllTypes($request);
        $sort = $request->query->get('sort');

        $this->logger->debug(__METHOD__.'::controller filters '.json_encode($allFilters).' sort = '.$sort);
        $result = $this->itemsService->view($itemType, $allFilters, $search, $sort, $itemId, $viewType, false, $viewOnly);
        
        if(($res = $this->checkResult($request, $result)) !== 0) return $res;
        
        // View type is modified by the service class if it was blank
        $vars = (array)$result;
        $vars['flash_edit_message'] = '';
        $vars['flash_syncUser_message'] = '';
        //var_dump($this->session->getFlashBag());
        foreach($this->session->getFlashBag()->get('edit.message') as $msg)
        {
            $vars['flash_edit_message'] .= $msg;
        }
        foreach($this->session->getFlashBag()->get('syncUser.message') as $msg)
        {
            $vars['flash_syncUser_message'] .= $msg;
        }
        foreach($this->session->getFlashBag()->get('error.message') as $msg)
        {
            $vars['flash_error_message'] .= $msg;
        }
        //var_dump($vars);//exit;

        $vars['pageTitle'] = '';

        if(isset($result->listing->itemType))
        {
            $vars['pageTitle'] = $result->listing->itemType->getPluralLabel();
        }

        if(isset($result->get->item))
        {
            $vars['pageTitle'] .= ' #'.$result->get->item->id.': '.$result->get->item->title;
        }

        switch($result->viewType)
        {
            case ItemsService::VIEW_DEFAULT:
                return $this->view->renderResponse('items/main.html.twig', $vars);
                break;
            case ItemsService::VIEW_TREE:
                return $this->view->renderResponse('items/tree.html.twig', $vars);
                break;
        }
    }

    /**
     * Save changes to an item, display the view() action with the item selected as current
     *
     * @param $itemType
     * @param $filters -- get param
     * @param $id -- get param
     *
     * @Route("/{itemType}", name="items_edit")
     * @Route("/{itemType}/{filters}", name="items_edit_filtered");
     * @Method("POST")
     */
    public function editAction(Request $request, $itemType)
    {
        $itemId = $request->request->get('id');
        $allFilters = $this->getFiltersForAllTypes($request);
        $prevVer = $request->request->get('prev_ver');
        $data = $request->request->get('item');
        $files = $request->files->get('files');
        $cmd = $request->request->get('cmd');
        $sort = $request->query->get('sort');
        $sendNotifications = $request->request->get('sendNotifications');

        if(is_array($data) && count($data) > 0) {
            // Look for command values to handle for item's fields and sub_fields
            foreach($data as $itemId => $itemData)
            {
                // Handle subfield commands
                $this->logger->debug('*****'.$itemId);
                if(is_array($cmd[$itemId]))
                {
                    foreach($itemData as $fieldId => $fieldData)
                    {
                        $this->logger->debug('^^^^^'.$fieldId);
                        if(array_key_exists($fieldId, $cmd[$itemId]))
                        {
                            switch($cmd[$itemId][$fieldId])
                            {
                                case 'delete':
                                    $this->logger->debug('DELETE::'.$fieldId.':'.$fieldId);
                                    unset($data[$itemId][$fieldId]);
                                    break;
                            }
                            if(is_array($cmd[$itemId][$fieldId]))
                            {
                                foreach($fieldData as $subFieldId => $subFieldData)
                                {
                                    $this->logger->debug('.....'.$fieldId.'^'.$subFieldId);
                                    if(array_key_exists($subFieldId, $cmd[$itemId][$fieldId]))
                                    {
                                        switch($cmd[$itemId][$fieldId][$subFieldId])
                                        {
                                            case 'delete':
                                                $this->logger->debug('DELETE::'.$fieldId.':'.$fieldId.'^'.$subFieldId);
                                                unset($data[$itemId][$fieldId][$subFieldId]);
                                                break;
                                        }
                                    }
                                }
                            }

                        }
                    }
                }
            }
            //$this->logger->debug(print_r($cmd, true));
            //exit;


            //$this->logger->debug(__METHOD__.'::params'.json_encode(['itemType' => $itemType, 'filters' => $filters, 'itemId' => $itemId]));

            // echo '<pre>';
            // var_dump($data);
            // var_dump($files);
            // exit;
            $result = $this->itemsService->edit($itemType, $allFilters, $sort, $itemId, $prevVer, $data, $files ? $files : [], $cmd ? $cmd : [], $sendNotifications);
            if(($res = $this->checkResult($request, $result)) !== 0) return $res;

            if(isset($result->edit->message)) $this->session->getFlashBag()->add('edit.message', $result->edit->message);
            if(isset($result->syncUser->message)) $this->session->getFlashBag()->add('syncUser.message', $result->syncUser->message);
            //dump($result);exit;
        } else {
            if(isset($result->edit->message)) $this->session->getFlashBag()->add('error.message', 'No data in edit request');
        }

        if($result->edit->error != \AppBundle\Service\ArionErrors::NONE)
        {
            // Non-redirect response for critical errors only -- these errors must be
            // reposted by user or data will be lost
            $vars = (array)$result;
            $vars['pageTitle'] = '#'.$result->get->item->id.' &mdash; '.$result->get->item->title;
            return $this->view->renderResponse('items/main.html.twig', $vars);
        } else {
            // Redirect to view request for same filters / item id
            return new RedirectResponse(str_replace('viewOnly=1', '', $result->viewUrl).$result->get->item->id);
        }
        
    }

    /**
     * Get filters for all item types, which will be separated as needed in ItemsService.
     * Keep in mind that symfony changes all periods in GET keys to underscores.
     *
     * @param $request
     * @return array
     */
    private function getFiltersForAllTypes($request)
    {
        $filters = [];
        $params = $request->query->all();
        
        foreach($params as $key => $value)
        {
            if(substr($key, 0, strlen('filters_')) == 'filters_')
            {
                $filters[$key] = $value;
            }
        }
        return $filters;
    }

}
