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

class BaseServiceResult implements IServiceResult {
    public $error = ArionErrors::NONE;
    public $error_message = '';
    public $message = '';

    public function getError() {
    	return $this->error;
    }

    public function getErrorMessage() {
    	return $this->error_message;
    }

    public function getMessage() {
    	return $this->message;
    }
}
