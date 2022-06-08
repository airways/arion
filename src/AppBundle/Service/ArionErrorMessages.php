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

class ArionErrorMessages {
	const NONE = '';
	const INVALID_ITEM_TYPE = 'Invalid item type "%s"';
	const ITEM_DATA_MISSING = 'There is no data for item "%d" in your request';
	const ITEM_SAVED = 'Item "%d" saved!';
	const ITEM_NOT_SAVED = 'Item "%d" could not be saved!';
	const ITEM_ID_REQUIRED = 'Item ID is required for this request.';
	const CANNOT_CREATE_ACCOUNT = 'Cannot create an account.';
	const USER_SYNC_PERFORMED = 'User "%s" was syncronized with this item.';
	const USER_SYNC_NEW_PASSWORD = 'The users password was set to "%s". This will not be shown again.';
    const USER_SYNC_INCOMPLETE_DATA = 'The item does not contain a value needed to create a user account: email, username, or password is missing.';
    const ITEM_NOT_SAVED_OUT_OF_DATE = '<b>Changes not saved</b><br>The item has already been modified by someone else. Please resolve conflicts highlighted as <div class="diff-html-removed">New Version</div>, <div class="diff-html-added">Your Version</div> or <div class="diff-html-changed">Both Changed</div> then try saving again.';
    const KEY_NOT_SET = 'Key not set';
}
