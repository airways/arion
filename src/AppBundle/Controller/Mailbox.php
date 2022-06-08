<?php

/**
 * Mailbox controller to view raw imported mail
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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

use AppBundle\Service\SiteService;
use AppBundle\Service\MailboxService;

/**
 * @Route(service="app.mailbox_controller")
 */
class Mailbox extends BaseController {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \mako\view\ViewFactory
     */
    protected $view;

    /**
     * @var \app\lib\MailboxService
     */
    protected $mailboxService;

    /**
     * @var \app\lib\SiteService $siteService
     */
    protected $siteService;

    public function __construct(\Psr\Log\LoggerInterface $logger, 
                                MailboxService $mailboxService,
                                SiteService $siteService)
    {
        $this->logger = $logger;
        $this->view = $view;
        $this->mailboxService = $mailboxService;
        $this->siteService = $siteService;
    }

    /**
     * Display a listing and first item for a particular item type, possibly with filters
     * applied
     *
     * route /arion/mailbox/
     * example_route /arion/mailbox/                  # Show all messages from all mailboxes for the user
     * example_route /arion/mailbox/box:35/100        # Load message 100 from mailbox ID 35
     * example_route /arion/mailbox/box:35/           # Only show messages from mailbox ID 35
     *
     * @Route("/mailbox", name="mailbox")
     * @Route("/mailbox/{filters}", name="mailbox_filtered")
     */
    public function index()
    {
        // Remaining segments are filter options
        $filters = array();
        $mailbox_id = 0;
        $mailbox_message_id = 0;

        for($i = 3; $i <= 10; $i++)
        {
            $filter = $this->siteService->segment($i);

            if($filter)
            {
                if(!is_numeric($filter))
                {
                    $filter = explode(':', $filter);
                    if(count($filter) == 2) {
                        $filters[$filter[0]] = $filter[1];
                    }
                } else {
                    $mailbox_message_id = (int)$filter;
                }
            }
        }

        if(array_key_exists('box', $filters)) {
            $mailbox_id = (int)$filters['box'];
        }

        // Load the service and call corresponding method for this controller action
        $result = $this->mailboxService->message_list($filters);

        if(!$mailbox_message_id && count($result->mailbox_messages))
        {
            $mailbox_id = $result->mailbox_messages[0]->mailbox_id;
            $mailbox_message_id = $result->mailbox_messages[0]->id;
        }

        if($mailbox_message_id)
        {
            $current_message = $this->mailboxService->get($mailbox_id, $mailbox_message_id);
        } else {
            $current_message = NULL;
        }

        // Fill in additional view variables
        $vars = (array)$result;
        $vars['current_message'] = $current_message;
        $vars['view_url'] = $this->siteService->url('mailbox');

        return $this->view->create('mailbox.index', $vars);
    }

}
