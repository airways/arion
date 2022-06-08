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

use AppBundle\Service\SiteService;
use AppBundle\Service\BaseServiceResult;
use Symfony\Component\HttpFoundation\Request;

class BaseController {

	/**
	 * Check the result for:
	 *		- If this is a JSON format request, dump out JSON data and exit the script
	 *		- Fatal error screen
	 */
	protected function checkResult(Request $request, BaseServiceResult $result)
	{
		$format = $request->query->get('format');
		if($format == 'json') {
            echo json_encode($result, JSON_PRETTY_PRINT);
            exit;
        }

        if($result->getError()) {
            //exit('ERROR::'.$result->getError().'::'.$result->getErrorMessage());
            return $this->view->renderResponse('global/error.html.twig', (array)$result);
        }

        return 0;
	}

}
