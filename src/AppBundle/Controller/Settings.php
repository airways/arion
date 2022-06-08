<?php

/**
 * Settings controller to allow user to change their settings / preferences
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
use AppBundle\Service\SiteService;
use AppBundle\Service\SettingsService;

/** @Route("/settings", service="app.settings_controller") */
class Settings extends BaseController {

    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface $logger
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
     * @var \AppBundle\Service\SettingsService
     */
    protected $settingsService;



    public function __construct(LoggerInterface $logger, RouterInterface $router, EngineInterface $view,
                                SettingsService $settingsService)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->view = $view;
        $this->settingsService = $settingsService;
    }


    /**
     * Display and handle the settings form
     *
     * @Route("/user", name="userSettings")
     */
    public function userSettingsAction(Request $request)
    {
        if($request->isMethod('POST'))
        {
            $settings = $request->request->get('settings');
            if(!$settings) $settings = [];
            
            $changePassword = $request->request->get('changePassword');
            if(!$changePassword) $changePassword = [];

            $result = $this->settingsService->setUserSettings($settings, $changePassword);
            /*
            if($result->success)
            {
                if(!$request->isXMLHttpRequest())
                {
                    return new RedirectResponse($this->router->generate('home',
                                        [],
                                        UrlGeneratorInterface::ABSOLUTE_URL));
                }
            }
            */
        } else {
            $result = $this->settingsService->getUserSettings();
        }
        return $this->view->renderResponse('settings/user.html.twig', (array)$result);
    }

}
