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

namespace Tests\AppBundle\Unit;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TextMacroServiceTest extends KernelTestCase
{
    public function setUp()
    {
        $collectedMessages = [];
        $logger = $this->getMock('\Psr\Log\LoggerInterface');
        $logger
            ->expects($this->any())
            ->method('debug')
            ->will($this->returnCallback(
                function ($message) use (&$collectedMessages) {
                    $collectedMessages[] = $message;
                }
            ));

        $router = $this->getMock('\Symfony\Component\Routing\RouterInterface');
        $router->expects($this->any())
               ->method('generate')
               ->will($this->returnValue('http://arion.dev/items/tickets'));
        
        $authService = $this->getMockBuilder(\AppBundle\Service\AuthService::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $itemType = $this->getMockBuilder(\AppBundle\Entity\ItemType::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemType->expects($this->any())
              ->method('getPluralName')
              ->will($this->returnValue('tickets'));
        
        $item = new \AppBundle\Entity\Item();
        $item->setId(126);
        $item->setTitle('New Item Test incoming message');
        $item->setItemType($itemType);

        $items = $this->getMockBuilder(\AppBundle\Entity\ItemRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $items->expects($this->any())
              ->method('findItems')
              ->will($this->returnValue([$item]));
        
        $siteService = $this->getMockBuilder(\AppBundle\Service\SiteService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $autoLinkService = new \AppBundle\Service\AutoLinkService($logger, $router);
        $this->textMacroService = new \AppBundle\Service\TextMacroService($logger, $router, $authService,
                                                                     $items, $autoLinkService, $siteService);
        
    }

    public function testItemLinksWork()
    {
        $input = '#0126';
        $expect = '<a href="http://arion.dev/items/tickets?id=126">#0126: New Item Test incoming message</a>';

        $output = $this->textMacroService->processMacros($input);

        $this->assertEquals($expect, $output, 'Item links work');
    }

    public function testItemLinksDontDoubleTitles()
    {
        $input = '#0126: New Item Test incoming message';
        $expect = '<a href="http://arion.dev/items/tickets?id=126">#0126: New Item Test incoming message</a>';

        $output = $this->textMacroService->processMacros($input);

        $this->assertEquals($expect, $output, 'Item links don\'t double titles');
    }


    public function testItemLinksNotDoubled()
    {
        $input = '<a href="http://arion.dev/items/tickets?id=126">#0126: New Item Test incoming message</a>';
        $expect = $input;

        $output = $this->textMacroService->processMacros($input);

        $this->assertEquals($expect, $output, 'Item links are not doubled');
    }

    public function testAutoLinksWork()
    {
        $input = 'http://google.com/';
        $expect = '<a class="url" href="http://google.com/" rel="external nofollow" target="_blank">http://google.com/</a>';

        $output = $this->textMacroService->processMacros($input);

        $this->assertEquals($expect, $output, 'Autolinks work');
    }

    public function testAutoLinksNotDoubled()
    {
        $input = '<a class="url" href="http://google.com/" rel="external nofollow" target="_blank">http://google.com/</a>';
        $expect = $input;

        $output = $this->textMacroService->processMacros($input);

        $this->assertEquals($expect, $output, 'Autolinks are not doubled');
    }

    public function testIndentedCodeNotMangled()
    {
        $input = <<<END
Test code

    http://google.com
END;
        $expect = $input;
        $output = $this->textMacroService->processMacros($input);

        $this->assertEquals($expect, $output, 'Indented code not mangled');
    }
}



