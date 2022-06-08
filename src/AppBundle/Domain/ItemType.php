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

 
namespace AppBundle\Domain;

use Psr\Log\LoggerInterface;

class ItemType {

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @var \AppBundle\Entity\ItemType
     */
	protected $modelItemType;

	public function __construct(\AppBundle\Entity\ItemType $modelItemType, LoggerInterface $logger) {
		$this->logger = $logger;

		$this->modelItemType = $modelItemType;
	}

    public function __get($key)
    {
        return $this->modelItemType->$key;
    }

    public function __set($key, $value)
    {
        return $this->modelItemType->$key = $value;
    }

    public function __call($method, $args)
    {
        if(method_exists($this->modelItemType, 'get'.ucwords($method))) $method = 'get'.ucwords($method);
        return call_user_func_array([$this->modelItemType, $method], $args);
    }
    /**
     * Return fields with isFilter=true
     *
     * @return array of AppBundle\Entity\Field
     */
    public function filters()
    {
        $result = [];
        foreach($this->modelItemType->getFields() as $field)
        {
            if($field->getIsFilter()) $result[] = $field;
        }
        return $result;
    }

}
