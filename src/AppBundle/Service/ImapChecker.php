<?php

/**
 * IMAP based email checker library. Called by EmailChecker controller cron job.
 *
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
use \Swift_Message;
use \Swift_Mailer;
use Defuse\Crypto\Crypto;

// Framework
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\ExtensionGuesser;

// Project
use AppBundle\Service\AuthService;
use AppBundle\Entity\MailboxRepository;
use AppBundle\Entity\MailboxMessageRepository;
use AppBundle\Entity\ItemRepository;
use AppBundle\Entity\UserRepository;
use AppBundle\Service\ArionErrors;
use AppBundle\Service\ArionErrorMessages;

class ImapChecker {

    /**
	 *
	 * @var AuthService
	 */
    protected $session;
    
    /**
	 *
	 * @var \Psr\Log\LoggerInterface
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
     * @var MailboxRepository
     */
    protected $mailboxes;

    /**
     * @var MailboxMessageRepository
     */
    protected $mailboxMessage;

    /**
     * @var AppBundle\Service\ItemsService
     */
    protected $items;

    /**
     * @var AppBundle\Service\AuthService
     */
    protected $authService;

    /**
     * @var AppBundle\Service\EncryptionService
     */
     protected $encryption;

    /**
     * @var AppBundle\Entity\UserRepository
     */
     protected $users;

    /**
     * @var AppBundle\Service\TextMacroService
     */
     protected $textMacroService;

	public function __construct(LoggerInterface $logger, EngineInterface $view, Swift_Mailer $mailer,
                                    AuthService $session, 
                                    MailboxRepository $mailboxes,
                                    MailboxMessageRepository $mailboxMessages,
                                    TextMacroService $textMacroService,
                                    ItemsService $items, EncryptionService $encryption,
                                    UserRepository $users, AuthService $authService)
	{
        $this->session = $session;
    	$this->logger = $logger;
        $this->view = $view;
        $this->mailboxes = $mailboxes;
        $this->mailboxMessages = $mailboxMessages;
        $this->items = $items;
        $this->encryption = $encryption;
        $this->users = $users;
        $this->authService = $authService;
        $this->mailer = $mailer;
        $this->textMacroService = $textMacroService;
	}


    public function checkEmail($accountId, $key)
    {
        $this->logger->debug('');
        $this->logger->debug('******************** '.__METHOD__.' ********************');
        $this->logger->debug('accountId: '.$accountId);
        $this->logger->debug('key length: '.strlen($key));

        
        $this->session->setApiLogin($accountId, $key);
        
        $result = new ImapCheckerResult();
        $result->success = false;
        $this->logger->info(__METHOD__.'::Checking email for all users');
        
		$mailboxes = $this->mailboxes->findAll();

        $this->logger->info(__METHOD__.'::Listing mailboxes');
        
        $count = 0;

        if(count($mailboxes) == 0)
        {
            $result->success = true;
            $result->message = "No mailboxes configured.";
        }

        foreach ($mailboxes as $mailboxNo => $mailbox) {
            $this->logger->info(__METHOD__.'::'.__LINE__.'::Mailbox '.$mailboxNo.'; username=@'.$mailbox->getUsername().'@ server=@'.$mailbox->getServer().'@');
            // DEBUG $this->logger->info(__METHOD__.'::'.__LINE__.'::'.$mailbox->getUsername().'::'.$mailbox->getPassword());

            $server = new \Fetch\Server($mailbox->getServer(), $mailbox->getPort());
            $server->setFlag('imap');
            
            $this->logger->info(__METHOD__.'::'.__LINE__.'::'.$server->getServerString());
            // TODO: why can we not validate good certs?
            $server->setFlag('novalidate-cert');
            //$server->setFlag('tls');
            //$server->setParam('DISABLE_AUTHENTICATOR', 'PLAIN');


            
            $server->setAuthentication($mailbox->getUsername(), $this->encryption->decrypt($mailbox->getPassword()));

            $server->setMailbox($mailbox->getMailboxName());


            $messages = $server->getMessages();
           
            foreach ($messages as $imapMessage) {
                // See if message already exists in database, skip if it does
                if(!$this->mailboxMessages->findOneBy(['mailboxId' => $mailbox->getId(),
                                      'imapUid' => $imapMessage->getUid()]))
                {
                    // Create new mailbox message record for message
                    $this->logger->debug(' ********** new message '.$imapMessage->getUid()
                                         .'; Subject: '.$imapMessage->getSubject()
                                         .'; Date: '.$imapMessage->getDate().' **********');
                    $headers = $imapMessage->getHeaders();
                    $this->logger->debug(__METHOD__.'::headers '.json_encode($headers));
                    
                    $data = [
                        'accountId' => $mailbox->getAccountId(),
                        'mailboxId' => $mailbox->getId(),
                        'uid' => $imapMessage->getUid(),
                        'subject' => $imapMessage->getSubject(),
                        'date' => new \DateTime(date("Y-m-d H:i:s", $imapMessage->getDate())),
                        'body' => $imapMessage->getMessageBody(true),
                        'to' => isset($headers->toaddress) ? $headers->toaddress : '',
                        'from' => isset($headers->fromaddress) ? $headers->fromaddress : '',
                        'cc' => isset($headers->ccaddress) ? $headers->ccaddress : '',
                    ];

                    $this->logger->debug('message data: '.json_encode($data));

                    $message = $this->mailboxMessages->createMessage(
                        $data['accountId'],
                        $data['mailboxId'],
                        $data['uid'],
                        $data['subject'],
                        $data['date'],
                        $data['body'],
                        $data['to'],
                        $data['from'],
                        $data['cc']
                    );
                    
                    $count++;

                    // Find a contact for the from address
                    $from_email = $headers->from[0]->mailbox.'@'.$headers->from[0]->host;
                    $fromEmailParsed = $this->parseEmail($from_email);

                    $contact = $this->findContact($from_email);

                    if($contact)
                    {
                        $this->logger->debug('contact: '.$contact->email);

                        // Find the user for this contact
                        $postingUser = $this->users->getUserByItemId($mailbox->getAccountId(), $contact->id);
                    } else {
                        $postingUser = false;
                    }

                    if(!$postingUser)
                    {
                        // Try to find user directly
                        $postingUser = $this->users->getUserByEmailAddress($fromEmailParsed->email);
                        if($postingUser)
                        {
                            $this->logger->debug('********** found internal user: '.$fromEmailParsed->email.' '.$postingUser->getId());
                        } else {
                            // TODO new lead, create a contact and lead record
                            $this->logger->debug('********** email address does not have a user assocaited with it: '.$fromEmailParsed->email);
                            $replyMessage = 'We are sorry but your current email address is not known to the system. Please reply from the same email address you recieve notifications at.';
                        }
                        $restrictedUserOwnerItemType = 0;
                        $restrictedUserOwnerItemId = 0;
                    } else {
                        $this->logger->debug('********** found restricted user: '.$fromEmailParsed->email.' '.$postingUser->getId());
                        list($restrictedUserOwnerItemType, $restrictedUserOwnerItemId) = $this->authService->getRestrictedUserKey($mailbox->getAccountId(), $postingUser->getId());
                    }
            
                    // TODO: Find contacts for all CC fields if they exist

                    if($postingUser)
                    {
                        $this->logger->debug('********** try to find item id');

                        // Parse for item ID
                        $itemId = false;
                        $itemToComment = false;
                        $itemToCommentViewUrl = false;

                        foreach([$data['subject'], $data['body']] as $value)
                        {
                            if(preg_match('/Ticket Updated: #(\d+)/i', $value, $matches))
                            {
                                $itemId = (int)$matches[1];
                                $this->logger->debug('***** Found item ID{method 1} in subject or body: '.$itemId);
                                break;
                            }
                            
                            /*
                            if(preg_match('/[#(\d+)]/i', $value, $matches))
                            {
                                var_dump($matches);
                                $itemId = (int)$matches[1];
                                $this->logger->debug('***** Found item ID{method 2} in subject or body: '.$itemId);
                                break;
                            }
                            */

                            if(preg_match('#items/tickets.*id=(\d+)#', $value, $matches))
                            {
                                $itemId = (int)$matches[1];
                                $this->logger->debug('***** Found item ID{method 3} in subject or body: '.$itemId);
                                break;
                            }
                            
                        }
                        $this->logger->debug('********** itemId result = '.$itemId);
                        
                        $searchSubject = $data['subject'];
                        $searchSubject = preg_replace('/(fw:|fwd:|re:)/i', '', $searchSubject);
                        $searchSubject = preg_replace('/(.*) Updated: #(\d+)/i', '', $searchSubject);
                        $searchSubject = trim($searchSubject);


                        if($itemId)
                        {
                            $getResponse = $this->items->get($itemId);
                            $itemToComment = $getResponse->item;
                            $itemToCommentViewUrl = $getResponse->viewUrl;
                        } else {
                            
                            $this->logger->debug('search for subject "'.$searchSubject.'"');

                            $filters = ['filters_tickets_summary' => $searchSubject];
                            if($restrictedUserOwnerItemId)
                            {
                                $filters['filters_tickets_client'] = $restrictedUserOwnerItemId;
                            }
                            $items = $this->items->view('tickets', $filters, '', '');
                            if(count($items->listing->items))
                            {
                                if(count($items->listing->items) == 1)
                                {
                                    $itemToComment = $items->get->item;
                                    $itemToCommentViewUrl = $items->get->viewUrl;

                                } else {
                                    $this->logger->debug('subject is ambiguous = "'.$searchSubject.'"');
                                    $itemToCommentViewUrl = $items->listing->viewUrl;
                                }
                            }
                        }

                        $this->logger->debug(__METHOD__.' check from addr for name to use in chopping sig '.json_encode($data['from']));
                        $parsedFrom = $this->parseEmail($data['from']);
                        if($parsedFrom->name && $parsedFrom->name !== $parsedFrom->email)
                        {
                            $this->logger->debug(__METHOD__.' truncate at signature ');
                            // Truncate the body, looking for a signature to chop off
                            $truncatedBody = $this->textMacroService->truncateEmailAndPurify($data['body'],
                                                                                $parsedFrom->name);
                            if(!trim($truncatedBody))
                            {
                                $this->logger->debug(__METHOD__.' entire message truncated{1}, try to truncate without signature ');
                                $truncatedBody = $this->textMacroService->truncateEmailAndPurify($data['body']);
                            }

                            
                        } else {
                            // Truncate the body without looking for signature
                            $this->logger->debug(__METHOD__.' truncate without signature ');
                            $truncatedBody = $this->textMacroService->truncateEmailAndPurify($data['body']);
                        }

                        if(!trim($truncatedBody))
                        {
                            $this->logger->debug(__METHOD__.' entire message truncated{2}, bail ');
                            $truncatedBody = $this->textMacroService->purify($data['body']);
                        }

                        $this->logger->debug($truncatedBody);


                        // Prep attachments
                        $generatedAttachmentFiles = [];
                        if($imapMessage->getAttachments())
                        {
                            foreach($imapMessage->getAttachments() as $attachment)
                            {
                                $generatedAttachmentFiles[] = new ImapUploadedFile($attachment);
                            }
                        }

                        // Post comment to item if found
                        $replyMessage = '';
                        if($itemToComment)
                        {
                            $this->logger->debug(__METHOD__.'::********** post comment to item '.$itemToComment->id.' for subject "'.$data['subject'].'"');

                            if($itemToComment->hasField('comments'))
                            {
                                $itemData = [
                                    $itemToComment->id => $itemToComment->getValues(),
                                ];
                                $itemFiles = [
                                    $itemToComment->id => [
                                        'files' => [$generatedAttachmentFiles],
                                    ]
                                ];
                                
                                /*
                                $comments = $itemToComment->comments;
                                if(!is_array($comments)) $comments = [[$comments]];
                                $comments[0][] = $truncatedBody;
                                $itemToComment->comments = $comments;
                                $itemToComment->getModelItem()->save($itemToComment->ver, $postingUser->getId(),
                                                                     $restrictedUserOwnerItemType,
                                                                     $restrictedUserOwnerItemId);
                                */

                                // If comments is not yet an array (there are no or one comment) then wrap
                                // the value in a nested array structure.
                                if(!is_array($itemData[$itemToComment->id]['comments']))
                                {
                                    $itemData[$itemToComment->id]['comments'] = [[$itemData[$itemToComment->id]['comments']]];
                                }

                                // Add the comment
                                $itemData[$itemToComment->id]['comments'][0][count($itemData[$itemToComment->id]['comments'][0])] = $truncatedBody;

                                // Trigger standard edit action which will commit change and send
                                // notifications as needed.
                                $this->logger->debug(__METHOD__.'***** Save Comment to Item');
                                $this->logger->debug(__METHOD__.print_r($itemData,true));
                                $this->logger->debug(__METHOD__.print_r($itemFiles, true));
                                //exit;
                                $this->items->edit('tickets', [], '', $itemToComment->id,
                                    $itemToComment->ver, $itemData, $itemFiles, [], true,
                                    $postingUser->getId());

                            } else {
                                $this->logger->debug('item does not have a comments field '.$itemToComment->id.' for summary "'.$searchSubject.'"');

                                $replyMessage = 'We are sorry but the item you have replied to does not have a comments field and so we cannot process this message. Please update this item on the website. ';
                                if($itemToCommentViewUrl)
                                {
                                    $replyMessage .= PHP_EOL.PHP_EOL.'<br><br><a href="'.$itemToCommentViewUrl.'">'.$itemToCommentViewUrl.'</a>';
                                } else {

                                }
                            }
                        } else {
                            // Create new item if no item ID was found
                            $this->logger->debug(__METHOD__.'::********** create new item for subject "'.$data['subject'].'"');
                            $this->logger->debug('Searched value = "'.$searchSubject.'"');

                            $itemData = [
                                'summary' => $data['subject'],
                                'description' => $data['body'],
                                'comments' => [['']],
                                'notes' => [['']],
                                'client' => '',
                                'project' => '',
                                'status' => 'open',
                                'assigned_to' => '',
                                'priority' => 1,
                            ];
                            if($restrictedUserOwnerItemId)
                            {
                                $itemData['client'] = $restrictedUserOwnerItemId;
                            }
                            $createResponse = $this->items->create('tickets', [], '', [], $postingUser->getId());
                            sleep(1);   // Sleep so that version # of edit is different

                            $getResponse = $this->items->get($createResponse->item->getId());
                            $attachToItem = $getResponse->item;
                            
                            $itemData = [
                                $attachToItem->id => $itemData,
                            ];

                            if(count($generatedAttachmentFiles) > 0)
                            {
                                $this->logger->debug(sprintf(
                                    '%s ********** attaching %d images from email to item', 
                                    __METHOD__, count($generatedAttachmentFiles)));

                                
                                
                                $itemFiles = [
                                    $attachToItem->id => [
                                        'files' => [$generatedAttachmentFiles],
                                    ]
                                ];

                                $this->logger->debug(__METHOD__.'***** Save Data and Attach Images to New Item');
                                $this->logger->debug(__METHOD__.print_r($itemData, true));
                                $this->logger->debug(__METHOD__.print_r($itemFiles, true));
                                //exit;

                                // Save data and files

                                //$itemTypeName, $allFilters, $sort, $itemId,
                                //$prev_ver, array $data, array $files, array $cmd, $sendNotifications=true, 
                                //$overrideUserId=false
                                $this->items->edit('tickets', [], '', $attachToItem->id,
                                    $attachToItem->ver, $itemData, $itemFiles, [], true,
                                    $postingUser->getId());
                            } else {
                                // Save data
                                // Edit ticket instead of passing to create() above to generate notifications
                                //$itemTypeName, $allFilters, $sort, $itemId,
                                //$prev_ver, array $data, array $files, array $cmd, $sendNotifications=true, 
                                //$overrideUserId=false
                                $this->logger->debug(__METHOD__.'***** Save Data');
                                $this->logger->debug(__METHOD__.print_r($itemData, true));
                                $this->items->edit('tickets', [], '', $attachToItem->id,
                                    $attachToItem->ver, $itemData, $itemData, [], true,
                                    $postingUser->getId());
                            }

                            $replyMessage = 'Thank you for your message, a new ticket has been created: '
                                            .PHP_EOL.PHP_EOL.'<br><br><a href="'.$createResponse->viewUrl.'">'.$createResponse->viewUrl.'</a>';

                        }

                    }

                    if($replyMessage)
                    {
                        // Send reply back to sender to notify them of the status of their request
                        $replySubject = 'Re: '.$data['subject'];
                        $replyMessageTo = $this->parseEmail($data['from'])->email;

                        $this->logger->debug('Sending reply to '.$replyMessageTo.': "'.$replySubject.'" '.strip_tags($replyMessage));
                        $message = Swift_Message::newInstance()
                            ->setSubject($replySubject)
                            ->setFrom('system@metasushi [dot] com')
                            ->setTo($replyMessageTo)
                            ->setBody(
                                $body1=$this->view->render(
                                    'emails/basic.html.twig',
                                    [
                                        'subject' => $replySubject,
                                        'message' => $replyMessage,
                                    ]
                                ),
                                'text/html'
                            )
                            ->addPart(
                                $body2=$this->view->render(
                                    'emails/basic.txt.twig',
                                    [
                                        'subject' => $replySubject,
                                        'message' => strip_tags($replyMessage),
                                    ]
                                ),
                                'text/plain'
                            )
                            
                        ;
                        $this->logger->debug('message body1 = '.$body1);
                        $this->logger->debug('message body2 = '.$body2);
                        $replyResult = $this->mailer->send($message);
                        $this->logger->debug(__METHOD__.'::send email result::'.json_encode($replyResult));

                    }

                    $result->success = true;

                }
            }
        }
		
        $result->count = $count;
        return $result;
	}

    /**
     * Find a contact based on an email address, and return it. Create the contact if it does not exist.
     */
    private function findContact($email)
    {

        $contacts = $this->items->view('contacts', ['filters_contacts_email' => $email], '', '');
        if(count($contacts->listing->items) == 0)
        {
            
            $this->logger->debug(__METHOD__.'::contact not found');
            return false;
            /*
            // Contact does not exist, create it
            $data = ['email' => $email];
            $this->items->create('contacts', [], ['email' => $email]);

            //($itemType, $allFilters, $search="", $sort, $currentItemId=0, $viewType=false, $refresh=false)
            $contacts = $this->items->view('contacts', ['filters_contacts_email' => $email, '', ''], '', '');
            */
        }
        $contact = $contacts->get->item;

        $this->logger->debug(__METHOD__.'::returning contact '.$contact->id);
        return $contact;
    }

    /**
     * Parse a string formatted like "Test Testerson <test@example.com>" into Name and Email components.
     * If a plain email is provided "test@example.com" then the string will be returned for both
     * components.
     *
     */
    private function parseEmail($string)
    {
        $arr = explode('<', $string);
        $name = $arr[0];
        if(count($arr) == 1) $arr[1] = $arr[0];
        $email = $arr[1];
        $email = str_replace('>', '', $email);
        return (object)['name' => $name, 'email' => $email];
    }

}

class ImapCheckerResult extends BaseServiceResult {
    var $count = 0;
}

class ImapUploadedFile implements \Psr\Http\Message\UploadedFileInterface
{
    /** 
     * @var \Fetch\Attachment
     */
    protected $attachment;

    public function __construct(\Fetch\Attachment $attachment)
    {
        $this->attachment = $attachment;
    }

    public function getFd()
    {
        $f = fopen('php://memory', 'w+');
        fwrite($f, $this->attachment->getData());
        rewind($f);
        return new \GuzzleHttp\Psr7\Stream($f);
    }

    public function getRealPath()
    {
        return false;
    }

    public function getStream()
    {
        return new \GuzzleHttp\Psr7\Stream($this->getFd());
    }

    public function moveTo($targetPath)
    {
        file_put_contents($targetPath, $this->attachment->getData());
    }

    public function getSize()
    {
        return NULL;
    }
    
    public function getError()
    {
        return \UPLOAD_ERR_OK;
    }

    public function getClientFilename()
    {
        return $this->attachment->getFileName();
    }

    public function getClientOriginalName()
    {
        return $this->attachment->getFileName();
    }

    public function getClientOriginalExtension()
    {
        $info = new \SplFileInfo($this->attachment->getFileName());
        return $info->getExtension();
    }

    public function getClientMediaType()
    {
        return $this->attachment->getMimeType();
    }

    public function guessExtension()
    {
        $type = $this->attachment->getMimeType();
        $guesser = ExtensionGuesser::getInstance();

        return $guesser->guess($type);
    }

}
