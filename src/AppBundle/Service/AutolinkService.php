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

// Framework
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

// Project
use AppBundle\Service\AuthService;
use AppBundle\Entity\ItemRepository;
use AppBundle\Entity\MailboxMessageRepository;

class AutolinkService {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    private $accountId;
    private $userId;
    private $restrictedUserOwnerItemType;
    private $restrictedUserOwnerItemId;

    public function __construct(\Psr\Log\LoggerInterface $logger,
                                RouterInterface $router) {
        $this->logger = $logger;
        $this->router = $router;

        $this->url_base_user = $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->url_base_list = $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->url_base_hash = $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $this->url_base_cash = $this->router->generate('home', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function create() {
        return \Twitter\Text\Autolink::create();
    }
}
