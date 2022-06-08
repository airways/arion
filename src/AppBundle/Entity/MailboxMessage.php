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

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="mailbox_messages")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MailboxMessageRepository")
 */
class MailboxMessage {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="account_id", type="integer")
     */
    private $accountId;

    /**
     * @ORM\Column(name="mailbox_id", type="integer")
     */
    private $mailboxId;

    /**
     * @ORM\Column(name="imap_uid", type="integer")
     */
    private $imapUid;

    /**
     * @ORM\Column(name="`from`", type="string", length=256)
     */
    private $from;

    /**
     * @ORM\Column(name="`to`", type="string", length=256)
     */
    private $to;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $cc;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $subject;

    /**
     * @ORM\Column(type="string")
     */
    private $body;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;





    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set accountId
     *
     * @param integer $accountId
     *
     * @return MailboxMessage
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Get accountId
     *
     * @return integer
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * Set mailboxId
     *
     * @param integer $mailboxId
     *
     * @return MailboxMessage
     */
    public function setMailboxId($mailboxId)
    {
        $this->mailboxId = $mailboxId;

        return $this;
    }

    /**
     * Get mailboxId
     *
     * @return integer
     */
    public function getMailboxId()
    {
        return $this->mailboxId;
    }

    /**
     * Set imapUid
     *
     * @param integer $imapUid
     *
     * @return MailboxMessage
     */
    public function setImapUid($imapUid)
    {
        $this->imapUid = $imapUid;

        return $this;
    }

    /**
     * Get imapUid
     *
     * @return integer
     */
    public function getImapUid()
    {
        return $this->imapUid;
    }

    /**
     * Set from
     *
     * @param string $from
     *
     * @return MailboxMessage
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get from
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set to
     *
     * @param string $to
     *
     * @return MailboxMessage
     */
    public function setTo($to)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get to
     *
     * @return string
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set cc
     *
     * @param string $cc
     *
     * @return MailboxMessage
     */
    public function setCc($cc)
    {
        $this->cc = $cc;

        return $this;
    }

    /**
     * Get cc
     *
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * Set subject
     *
     * @param string $subject
     *
     * @return MailboxMessage
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return MailboxMessage
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return MailboxMessage
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
}
