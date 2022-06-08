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

class ArionErrors {
	const NONE = 0;
	const INVALID_ITEM_TYPE = 1000;
	const ITEM_DATA_MISSING = 1001;
	const ITEM_SAVED = 1002;
	const ITEM_NOT_SAVED = 1003;
	const ITEM_ID_REQUIRED = 104;
	const CANNOT_CREATE_ACCOUNT = 105;
    const USER_SYNC_INCOMPLETE_DATA = 106;
    const ITEM_NOT_SAVED_OUT_OF_DATE = 1007;
    const CANNOT_GET_ITEM = 1008;
    const KEY_NOT_SET = 1010;
}
