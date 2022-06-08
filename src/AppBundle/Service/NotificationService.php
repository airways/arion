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

// Psr / General Vendor
use Psr\Log\LoggerInterface;
use Rych\Random\Random;
use \Swift_Message;
use \Swift_Mailer;

// Framework
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

// Project
use \AppBundle\Entity\ItemTypeRepository;
use \AppBundle\Entity\ItemRepository;
use \AppBundle\Entity\UserRepository;

/**
 * Implements mail notifications
 */
class NotificationService {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;
    
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    protected $view;

    /**
     * @var MailerService
     */
    protected $mailer;

    /**
     * @var MailboxService
     */
    protected $mailboxeService;

    
    public function __construct(LoggerInterface $logger, EngineInterface $view,
                                Swift_Mailer $mailer, MailboxService $mailboxeService) {
        $this->logger = $logger;
        $this->view = $view;
        $this->mailer = $mailer;
        $this->mailboxeService = $mailboxeService;
    }

    public function sendNotification($toEmail, $subject, $message)
    {
        $this->logger->debug(__METHOD__.'::params::'.json_encode(['subject' => $subject, 'message' => $message]));

        $mailboxesResult = $this->mailboxeService->findMailboxes();
        if($mailboxesResult->error || count($mailboxesResult->mailboxes) > 0)
        {
            $fromAddress = $mailboxesResult->mailboxes[0]->getFromAddress();

            $message = Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($fromAddress)
                ->setTo($toEmail)
                ->setBody(
                    $this->view->render(
                        'emails/basic.html.twig',
                        [
                            'subject' => $subject,
                            'message' => $message,
                        ]
                    ),
                    'text/html'
                )
                ->addPart(
                    $this->view->render(
                        'emails/basic.txt.twig',
                        [
                            'subject' => $subject,
                            'message' => strip_tags($message),
                        ]
                    ),
                    'text/plain'
                )
                
            ;
            $result = $this->mailer->send($message);
            $this->logger->debug(__METHOD__.'::result::'.json_encode($result));
        } else {
            $this->logger->debug(__METHOD__.'::cannot send mail: '.$mailboxesResult->message);
        }

        //dump($result);
        //exit;

    }
}
