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
 * @ORM\Table(name="mailboxes")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MailboxRepository")
 */
class Mailbox {

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
     * @ORM\Column(name="user_id", type="integer")
     */
    private $userId;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $server;

    /**
     * @ORM\Column(type="integer")
     */
    private $port;

    /**
     * @ORM\Column(type="string", length=128)
     */
    private $username;

    /**
     * @ORM\Column(name="from_address", type="string", length=128)
     */
    private $fromAddress;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $password;
 
    /**
     * @ORM\Column(name="mailbox_name", type="string", length=128)
     */
    private $mailboxName;
   

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
     * @return Mailbox
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return Mailbox
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set server
     *
     * @param string $server
     *
     * @return Mailbox
     */
    public function setServer($server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Get server
     *
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * Set port
     *
     * @param integer $port
     *
     * @return Mailbox
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Get port
     *
     * @return integer
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return Mailbox
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set fromAddress
     *
     * @param string $fromAddress
     *
     * @return Mailbox
     */
    public function setFromAddress($fromAddress)
    {
        $this->fromAddress = $fromAddress;

        return $this;
    }

    /**
     * Get fromAddress
     *
     * @return string
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Mailbox
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set mailboxName
     *
     * @param string $mailboxName
     *
     * @return Mailbox
     */
    public function setMailboxName($mailboxName)
    {
        $this->mailboxName = $mailboxName;

        return $this;
    }

    /**
     * Get mailboxName
     *
     * @return string
     */
    public function getMailboxName()
    {
        return $this->mailboxName;
    }

}
