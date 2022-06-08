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
use AppBundle\Service\AuthService;

/**
 * @Route(service="app.default_controller")
 */
class DefaultController
{
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
     * @var \AppBundle\Service\AuthService
     */
    protected $auth;

    public function __construct(LoggerInterface $logger, RouterInterface $router, EngineInterface $view,
                                ItemsService $itemsService,
                                SiteService $siteService, SettingsService $settingsService,
                                AuthService $auth)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->view = $view;
        $this->itemsService = $itemsService;
        $this->siteService = $siteService;
        $this->settingsService = $settingsService;
        $this->auth = $auth;
    }


    /**
     * Redirect the user to their default item type, which is "tickets" for default user accounts
     *
     * @Route("/", name="home")
     */
    public function indexAction(Request $request)
    {
        /*
        $defaultItemType = 'tickets'; // TODO: get user's preference $this->userPrefs->get('default.item.type');
        return new RedirectResponse($this->router->generate('items',
                                    ['itemType' => $defaultItemType],
                                    UrlGeneratorInterface::ABSOLUTE_URL));
        */
        if(!$this->auth->isLoggedIn())
        {
            return new RedirectResponse('/auth/login');
        } else {
            $openWindow = $request->query->get('openWindow');
            return $this->view->renderResponse('default/shell.html.twig', [
                'openWindow' => $openWindow,
            ]);
        }
    }
}
