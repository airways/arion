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

namespace AppBundle\Fields\Meta;

use Psr\Log\LoggerInterface;
use AppBundle\Entity\ItemRepository;
use AppBundle\Entity\ItemValueRepository;
use AppBundle\Entity\ItemTypeRepository;
use AppBundle\Entity\UserRepository;
use AppBundle\Service\AuthService;

class BaseFieldMeta {

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var AppBundle\Entity\ItemRepository
     */
    protected $items;

    /**
     * @var AppBundle\Entity\ItemTypeRepository
     */
    protected $itemTypes;
    
    /**
     * @var AppBundle\Entity\UserRepository
     */
    protected $users;
    
    /**
     * @var AppBundle\Service\AuthService
     */
    protected $authService;

    protected $cache = [];
    
    public function __construct(LoggerInterface $logger,
                                ItemRepository $items, 
                                ItemTypeRepository $itemTypes,
                                UserRepository $users,
                                AuthService $authService)
    {
        $this->logger = $logger;
        $this->items = $items;
        $this->itemTypes = $itemTypes;
        $this->users = $users;
        $this->authService = $authService;
    }

}
