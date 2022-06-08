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

use Psr\Log\LoggerInterface;
use AppBundle\Service\AuthService;
use AppBundle\Entity\ItemTypeRepository;

class SiteService {

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var AppBundle\Service\AuthService
     */
    protected $authService;

    /**
     * @var AppBundle\Entity\ItemTypeRepository
     */
    protected $itemTypes;

    /**
     * @var \HTMLPurifier
     */
    protected $htmlPurifier;

    public $baseUrl;
    public $actionUrl;
    public $searchUrl;
    public $searchQuery;

    protected $segments = array();

    public function __construct(LoggerInterface $logger, AuthService $authService, ItemTypeRepository $itemTypes,
                                \HTMLPurifier $htmlPurifier)
    {
        $this->logger = $logger;
        $this->authService = $authService;
        $this->itemTypes = $itemTypes;
        $this->htmlPurifier = $htmlPurifier;
        $this->htmlPurifier->config->set('Attr.AllowedRel', 'external,nofollow');
        $this->htmlPurifier->config->set('Attr.AllowedFrameTargets', '_blank');
        $this->htmlPurifier->config->set('AutoFormat.RemoveEmpty', true);
        // echo 'alllowed=';
        // print_r($this->htmlPurifier->config->get('CSS.AllowedProperties'));
        // exit;
        $this->htmlPurifier->config->set('CSS.AllowedProperties', []);

        $this->searchQuery = array_key_exists('q', $_GET) ? $_GET['q'] : '';
    }

    public function anchor($url, $label)
    {
        $url = $this->url($url);
        return '<a href="'.$url.'">'.$label.'</a>';
    }

    public function isAuthRequest() {
        return $this->segment(1) == "auth";
    }

    public function getSegments() {
        if(count($this->segments) == 0)
        {
            $url = $_SERVER['REQUEST_URI'];

            // Remove base URL from request URL
            $segments = explode('/', $url);

            // If the first segment is a .php file, remove it
            if(substr($segments[0], -4) == '.php') unset($segments[0]);

            // Renumber segments array so 0 == first, 1 == second, etc.
            $this->segments = array_values($segments);
        }

        return $this->segments;
    }

    public function segment($index, $default='')
    {
        $segments = $this->getSegments();
        return array_key_exists($index, $segments) ? $segments[$index] : $default;
    }

    public function currentItemTypeName()
    {
        if($this->segment(0) == 'items')
        {
            return $this->segment(1);
        } else {
            return '';
        }
    }

    public function onMailbox()
    {
        return $this->segment(0) == 'mailbox';
    }

    public function getItemTypesForActiveUser()
    {
        return $this->itemTypes->findItemTypes($this->authService->getAccountId(), $this->authService->getUserId());
    }

    /**
     * Truncate a message on the reply indicator line, then pass the result
     * to purify() for further cleaning. Note unlike purify() we accept only
     * a single string.
     *
     * @param $string string
     * @return mixed
     */
    public function truncateEmailAndPurify($string, $emailFromName=false)
    {
        //$this->debug = [];
        $truncatedBody = $this->purify($string);

        if($emailFromName)
        {
            //$this->debug[] = 'emailFromName...';

            //$this->debug[] = $truncatedBody;
            if(preg_match('/<b>(.*?)'.preg_quote($emailFromName).'/im', $truncatedBody, $matches, PREG_OFFSET_CAPTURE))
            {
                //$this->debug[] = $matches;
                $pos = $matches[0][1];
                $truncatedBody = trim(substr($truncatedBody, 0, $pos));
            }

            if(preg_match('/<div>'.preg_quote($emailFromName).'/im', $truncatedBody, $matches, PREG_OFFSET_CAPTURE))
            {
                //$this->debug[] = $matches;
                $pos = $matches[0][1];
                $truncatedBody = trim(substr($truncatedBody, 0, $pos));
            }

            if(preg_match('/^'.preg_quote($emailFromName).'/imU', $truncatedBody, $matches, PREG_OFFSET_CAPTURE))
            {
                //$this->debug[] = $matches;
                $pos = $matches[0][1];
                $truncatedBody = trim(substr($truncatedBody, 0, $pos));
            }
        }

        if(preg_match('/(&?[>&gt; ]*)On (.*) wrote:/m', $truncatedBody, $matches, PREG_OFFSET_CAPTURE))
        {
            $pos = $matches[0][1];
            $truncatedBody = trim(substr($truncatedBody, 0, $pos));
        }

        // TODO will need to check outgoing mailbox address when this is available to other
        // users.
        // This covers Outlook From: reply syntax (may optionall have "on behalf of"
        // etc between the two strings)
        if(preg_match('/From:(.*?)(mg.metasushi.net|mailgun.org)/im', $truncatedBody, $matches, PREG_OFFSET_CAPTURE))
        {
            $pos = $matches[0][1];
            $truncatedBody = trim(substr($truncatedBody, 0, $pos));
        }

        if(preg_match('/Subject:(.*)Ticket Updated: #\d+/im', $truncatedBody, $matches, PREG_OFFSET_CAPTURE))
        {
            $pos = $matches[0][1];
            $truncatedBody = trim(substr($truncatedBody, 0, $pos));
        }

        return $this->purify($truncatedBody);
    }

    /**
     * Pass data to HTMLPurifier, recursively walking objects and arrays to do so.
     *
     * @param $data array/object/string
     * @param $allowConflictFlags if true, conflict classes will not be stripped
     * @return mixed
     */
    public function purify($data, $allowConflictFlags=false)
    {
        if(is_array($data) || is_object($data))
        {
            foreach($data as $key => $value) {            
                $value = $this->purify($value);
                
                if(is_array($data))
                {
                    $data[$key] = $value;
                } else {
                    $data[$key] = $value;
                }
            }
        } else {
            if(!$allowConflictFlags)
            {
                // Strip conflict marking tags
                //var_dump($data);

                while(preg_match_all('#<(span|s|del|ins) class="diff-html-(added|removed|changed)".*?>(.*?)</\1>#', $data, $matches) > 0)
                {
                    if(count($matches[0]) == 0) break;
                    //echo "matches:";
                    //var_dump($matches);
                    foreach($matches[0] as $i => $match)
                    {
                        $data = str_replace($match, $matches[3][$i], $data);
                    }
                }
                //echo 'after';
                //var_dump($data);
            }

            $data = $this->htmlPurifier->purify($data);


            // Do some final cleanup
            // $this->debug[] = 'before str replace';
            // $this->debug[] = $data;
            $data = str_ireplace(' class="MsoNormal"', '', $data);
            $data = str_ireplace('<span>', '', $data);
            $data = str_ireplace('</span>', '', $data);
            $data = str_ireplace('<p></p>', '', $data);
            $data = str_ireplace('<p> </p>', '', $data);
            $data = preg_replace('#(<br[ /]*>)+</p>#im', '</p>', $data);
            $data = preg_replace('#(<br[ /]*>)+</div>#im', '</div>', $data);
            $data = preg_replace('/ class="WordSection\d+"/', '', $data);

            // $this->debug[] = 'AFTER str replace';
            // $this->debug[] = $data;

            


        }

        return $data;
    }

    public function dumpAndExit($obj)
    {
        echo "<p><b>dumpAndExit</b></p>";
        dump($obj);
        exit;
    }
}

