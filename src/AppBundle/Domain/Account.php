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

namespace app\lib\domain;

use Psr\Log\LoggerInterface;

class Account implements \JsonSerializable {

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @var \app\models\Account
     */
	protected $modelAccount;

	public function __construct(\app\models\Account $modelAccount, LoggerInterface $logger) {
		$this->logger = $logger;
		$this->modelAccount = $modelAccount;
	}

    /**
     * Get a field from the modelAccount
     *
     * @param $key
     * @param $value
     */
    public function __get($key)
    {
        return $this->modelAccount->$key;
    }

    /**
     * Update a on the modelAccount
     *
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        return $this->modelAccount->$key = $value;
    }

    /**
     * Format the contents of the modelAccount's base record and values array into an array
     * to be serialized by json_encode.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $values = $this->modelAccount->toArray();
        return $values;
    }
}
