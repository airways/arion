<?php

/**
 * Cron job controller to fetch new mail messages.
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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Service\ImapChecker;

/** @Route("/email_check", service="app.emailchecker_controller") */
class EmailChecker extends BaseController {
    /**
     * @var \Symfony\Component\HttpKernel\Log\LoggerInterface $logger
     */
    protected $logger;
    protected $checker;
    
    public function __construct(LoggerInterface $logger, ImapChecker $checker)
    {
        $this->logger = $logger;
        $this->checker = $checker;
    }

    /**
     * @Route("/go", name="check_email")
     */
    public function check(Request $request)
    {
        $this->logger->info(__METHOD__);
        $accountId = $request->query->get('accountId');
        $key = $request->query->get('key');
    	$result = $this->checker->checkEmail($accountId, $key);
        
        return new JsonResponse($result);
	}
}
