<?php

/**
 * Encryption library wrapper
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

use Psr\Log\LoggerInterface;
use AppBundle\Service\AuthService;
use AppBundle\Entity\MailboxRepository;
use AppBundle\Entity\MailboxMessageRepository;
use AppBundle\Entity\ItemRepository;
use AppBundle\Service\ArionErrors;
use AppBundle\Service\ArionErrorMessages;
use Defuse\Crypto\Crypto;

class EncryptionService {

    private $key='';

    public function __construct($key='')
    {
        //Make a new key: exit(bin2hex(Crypto::createNewRandomKey()));
        //Encrypt a password: 
        //exit(bin2hex(Crypto::encrypt($pw, hex2bin($key))));
        $this->key = hex2bin($key);
    }

    public function encrypt($plainText)
    {
        if(strlen($this->key) < 16) throw new \Exception("Encryption key not set!");
        return bin2hex(Crypto::encrypt($plainText, $this->key));
    }

    public function decrypt($cipherText)
    {
        if(strlen($this->key) < 16) throw new \Exception("Encryption key not set!");
        return Crypto::decrypt(hex2bin($cipherText), $this->key);
    }
}
