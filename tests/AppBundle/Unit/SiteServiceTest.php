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

class SiteServiceTest extends KernelTestCase
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

        $itemTypes = $this->getMockBuilder(\AppBundle\Entity\ItemTypeRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemTypes->expects($this->any())
              ->method('getItemType')
              ->will($this->returnValue($itemType));

        $purifier = new \HTMLPurifier();
        $this->siteService = new \AppBundle\Service\SiteService($logger, $authService, $itemTypes,
                                                                     $purifier);
        
    }

    public function testScriptTagRemoved()
    {
        $input = '<p>This is a test!</p><script>alert("hello");</script>';
        $expect = '<p>This is a test!</p>';;

        $output = $this->siteService->purify($input);

        $this->assertEquals($expect, $output, 'Script tag removed');
    }

    public function testOutlookHTMLPurified()
    {
        $input = self::SST_OUTLOOK_MESSAGE_HTML;
        $expect = <<<END
<div>
<p>Thanks so much, Isaac!</p>

<p><b>Test Client Name<br />
Senior Manager, Digital Marketing<br />
Marketing Communications<br /></b>TEST CLIENT COMPANY</p>

</div>
END;
        $output = $this->siteService->truncateEmailAndPurify($input);
        $this->assertEquals($expect, $output, 'HTML garbage removed');
    }

    public function testOutlookHTMLPurifiedWithSignature()
    {
        $input = self::SST_OUTLOOK_MESSAGE_HTML;
        $expect = <<<END
<div>
<p>Thanks so much, Isaac!</p>

</div>
END;
        $output = $this->siteService->truncateEmailAndPurify($input, 'Test Client Name');
        $this->assertEquals($expect, $output, 'HTML garbage removed at signature');
    }


    public function testOutlookHTMLWithoutNewlinesPurified()
    {
        $input = str_replace("\n", '', str_replace("\r", '', self::SST_OUTLOOK_MESSAGE_HTML));
        $expect = <<<END
<div>
<p>Thanks so much, Isaac!</p>

<p><b>Test Client Name<br />
Senior Manager, Digital Marketing<br />
Marketing Communications<br /></b>TEST CLIENT COMPANY</p>

</div>
END;
        $expect = str_replace("\n", '', str_replace("\r", '', $expect));
        $output = $this->siteService->truncateEmailAndPurify($input);
        $this->assertEquals($expect, $output, 'HTML garbage without newlines removed');
    }

    public function testOutlookHTMLWithoutNewlinesPurifiedWithSignature()
    {
        $input = str_replace("\n", '', str_replace("\r", '', self::SST_OUTLOOK_MESSAGE_HTML));
        $expect = <<<END
<div>
<p>Thanks so much, Isaac!</p>
</div>
END;
        $expect = str_replace("\n", '', str_replace("\r", '',  $expect));
        $output = $this->siteService->truncateEmailAndPurify($input, 'Test Client Name');
        $this->assertEquals($expect, $output, 'HTML garbage without newlines removed at signature');
    }

    public function testReplyTruncatedCorrectly()
    {
        $input = self::SST_REPLY_MESSAGE_TEXT;
        $expect = <<<END
<div>Staging is back up and the build is partially in place but we still have a bit of configuration to do. Will update again when we are finished.</div>
END;
        $output = $this->siteService->truncateEmailAndPurify($input, 'Isaac Raway');
        //fwrite(STDERR, print_r($this->siteService->debug, TRUE));
        $this->assertEquals($expect, $output, 'Reply truncated properly');
    }

    const SST_REPLY_MESSAGE_TEXT = <<<END
<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"></head><body dir="auto"><div>Staging is back up and the build is partially in place but we still have a bit of configuration to do. Will update again when we are finished.<br><br><div><span style="background-color: rgba(255, 255, 255, 0);">Isaac Raway</span></div><div><span style="background-color: rgba(255, 255, 255, 0);">Owner, MetaSushi LLC d.b.a. RawaySmith</span></div><div><span style="background-color: rgba(255, 255, 255, 0);"><a href="http://rawaysmith.com">http://rawaysmith.com</a></span></div></div><div><br>On May 3, 2016, at 2:41 PM, <a href="mailto:system@metasushi [dot] com">system@metasushi [dot] com</a> wrote:<br><br></div><blockquote type="cite"><div>Message From Arion<br>
<b>Ticket Updated: #1161 EXAMPLE deployment 2016-05-05</b>
<br><br>
Item updated by Isaac Raway on May 03 at 2:41pm:<br><br>
<b>Internal Notes</b> modified:<br>
<em>Was</em>
<blockquote><div>Set expose threshold to 4</div></blockquote> 
<em>changed to</em> 
<blockquote><div>Set expose thresholds:</div><div>Logging=1</div><div>Reject=4</div></blockquote><br><br><br><br>
<a href="https://example.com/items/tickets?id=1161">https://example.com/items/tickets?id=1161</a><br>
--<br>
Reply directly to this email, or use the URL above.<br>
<b>Hint:</b> When replying via email please delete your email signature!
</div></blockquote></body></html>
END;

    const SST_OUTLOOK_MESSAGE_HTML = <<<END
<html xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns:m="http://schemas.microsoft.com/office/2004/12/omml" xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="ProgId" content="Word.Document">
<meta name="Generator" content="Microsoft Word 14">
<meta name="Originator" content="Microsoft Word 14">
<link rel="File-List" href="cid:filelist.xml@01D1A48F.CC97BCF0"><!--[if gte mso 9]><xml>
<o:OfficeDocumentSettings>
<o:AllowPNG/>
</o:OfficeDocumentSettings>
</xml><![endif]--><!--[if gte mso 9]><xml>
<w:WordDocument>
<w:SpellingState>Clean</w:SpellingState>
<w:GrammarState>Clean</w:GrammarState>
<w:TrackMoves/>
<w:TrackFormatting/>
<w:EnvelopeVis/>
<w:ValidateAgainstSchemas/>
<w:SaveIfXMLInvalid>false</w:SaveIfXMLInvalid>
<w:IgnoreMixedContent>false</w:IgnoreMixedContent>
<w:AlwaysShowPlaceholderText>false</w:AlwaysShowPlaceholderText>
<w:DoNotPromoteQF/>
<w:LidThemeOther>EN-US</w:LidThemeOther>
<w:LidThemeAsian>X-NONE</w:LidThemeAsian>
<w:LidThemeComplexScript>X-NONE</w:LidThemeComplexScript>
<w:Compatibility>
<w:DoNotExpandShiftReturn/>
<w:BreakWrappedTables/>
<w:SplitPgBreakAndParaMark/>
<w:EnableOpenTypeKerning/>
</w:Compatibility>
<m:mathPr>
<m:mathFont m:val="Cambria Math"/>
<m:brkBin m:val="before"/>
<m:brkBinSub m:val="&#45;-"/>
<m:smallFrac m:val="off"/>
<m:dispDef/>
<m:lMargin m:val="0"/>
<m:rMargin m:val="0"/>
<m:defJc m:val="centerGroup"/>
<m:wrapIndent m:val="1440"/>
<m:intLim m:val="subSup"/>
<m:naryLim m:val="undOvr"/>
</m:mathPr></w:WordDocument>
</xml><![endif]--><!--[if gte mso 9]><xml>
<w:LatentStyles DefLockedState="false" DefUnhideWhenUsed="true" DefSemiHidden="true" DefQFormat="false" DefPriority="99" LatentStyleCount="267">
<w:LsdException Locked="false" Priority="0" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Normal"/>
<w:LsdException Locked="false" Priority="9" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="heading 1"/>
<w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 2"/>
<w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 3"/>
<w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 4"/>
<w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 5"/>
<w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 6"/>
<w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 7"/>
<w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 8"/>
<w:LsdException Locked="false" Priority="9" QFormat="true" Name="heading 9"/>
<w:LsdException Locked="false" Priority="39" Name="toc 1"/>
<w:LsdException Locked="false" Priority="39" Name="toc 2"/>
<w:LsdException Locked="false" Priority="39" Name="toc 3"/>
<w:LsdException Locked="false" Priority="39" Name="toc 4"/>
<w:LsdException Locked="false" Priority="39" Name="toc 5"/>
<w:LsdException Locked="false" Priority="39" Name="toc 6"/>
<w:LsdException Locked="false" Priority="39" Name="toc 7"/>
<w:LsdException Locked="false" Priority="39" Name="toc 8"/>
<w:LsdException Locked="false" Priority="39" Name="toc 9"/>
<w:LsdException Locked="false" Priority="35" QFormat="true" Name="caption"/>
<w:LsdException Locked="false" Priority="10" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Title"/>
<w:LsdException Locked="false" Priority="1" Name="Default Paragraph Font"/>
<w:LsdException Locked="false" Priority="11" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Subtitle"/>
<w:LsdException Locked="false" Priority="22" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Strong"/>
<w:LsdException Locked="false" Priority="20" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Emphasis"/>
<w:LsdException Locked="false" Priority="59" SemiHidden="false" UnhideWhenUsed="false" Name="Table Grid"/>
<w:LsdException Locked="false" UnhideWhenUsed="false" Name="Placeholder Text"/>
<w:LsdException Locked="false" Priority="1" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="No Spacing"/>
<w:LsdException Locked="false" Priority="60" SemiHidden="false" UnhideWhenUsed="false" Name="Light Shading"/>
<w:LsdException Locked="false" Priority="61" SemiHidden="false" UnhideWhenUsed="false" Name="Light List"/>
<w:LsdException Locked="false" Priority="62" SemiHidden="false" UnhideWhenUsed="false" Name="Light Grid"/>
<w:LsdException Locked="false" Priority="63" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 1"/>
<w:LsdException Locked="false" Priority="64" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 2"/>
<w:LsdException Locked="false" Priority="65" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 1"/>
<w:LsdException Locked="false" Priority="66" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 2"/>
<w:LsdException Locked="false" Priority="67" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 1"/>
<w:LsdException Locked="false" Priority="68" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 2"/>
<w:LsdException Locked="false" Priority="69" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 3"/>
<w:LsdException Locked="false" Priority="70" SemiHidden="false" UnhideWhenUsed="false" Name="Dark List"/>
<w:LsdException Locked="false" Priority="71" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Shading"/>
<w:LsdException Locked="false" Priority="72" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful List"/>
<w:LsdException Locked="false" Priority="73" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Grid"/>
<w:LsdException Locked="false" Priority="60" SemiHidden="false" UnhideWhenUsed="false" Name="Light Shading Accent 1"/>
<w:LsdException Locked="false" Priority="61" SemiHidden="false" UnhideWhenUsed="false" Name="Light List Accent 1"/>
<w:LsdException Locked="false" Priority="62" SemiHidden="false" UnhideWhenUsed="false" Name="Light Grid Accent 1"/>
<w:LsdException Locked="false" Priority="63" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 1 Accent 1"/>
<w:LsdException Locked="false" Priority="64" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 2 Accent 1"/>
<w:LsdException Locked="false" Priority="65" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 1 Accent 1"/>
<w:LsdException Locked="false" UnhideWhenUsed="false" Name="Revision"/>
<w:LsdException Locked="false" Priority="34" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="List Paragraph"/>
<w:LsdException Locked="false" Priority="29" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Quote"/>
<w:LsdException Locked="false" Priority="30" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Intense Quote"/>
<w:LsdException Locked="false" Priority="66" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 2 Accent 1"/>
<w:LsdException Locked="false" Priority="67" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 1 Accent 1"/>
<w:LsdException Locked="false" Priority="68" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 2 Accent 1"/>
<w:LsdException Locked="false" Priority="69" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 3 Accent 1"/>
<w:LsdException Locked="false" Priority="70" SemiHidden="false" UnhideWhenUsed="false" Name="Dark List Accent 1"/>
<w:LsdException Locked="false" Priority="71" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Shading Accent 1"/>
<w:LsdException Locked="false" Priority="72" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful List Accent 1"/>
<w:LsdException Locked="false" Priority="73" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Grid Accent 1"/>
<w:LsdException Locked="false" Priority="60" SemiHidden="false" UnhideWhenUsed="false" Name="Light Shading Accent 2"/>
<w:LsdException Locked="false" Priority="61" SemiHidden="false" UnhideWhenUsed="false" Name="Light List Accent 2"/>
<w:LsdException Locked="false" Priority="62" SemiHidden="false" UnhideWhenUsed="false" Name="Light Grid Accent 2"/>
<w:LsdException Locked="false" Priority="63" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 1 Accent 2"/>
<w:LsdException Locked="false" Priority="64" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 2 Accent 2"/>
<w:LsdException Locked="false" Priority="65" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 1 Accent 2"/>
<w:LsdException Locked="false" Priority="66" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 2 Accent 2"/>
<w:LsdException Locked="false" Priority="67" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 1 Accent 2"/>
<w:LsdException Locked="false" Priority="68" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 2 Accent 2"/>
<w:LsdException Locked="false" Priority="69" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 3 Accent 2"/>
<w:LsdException Locked="false" Priority="70" SemiHidden="false" UnhideWhenUsed="false" Name="Dark List Accent 2"/>
<w:LsdException Locked="false" Priority="71" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Shading Accent 2"/>
<w:LsdException Locked="false" Priority="72" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful List Accent 2"/>
<w:LsdException Locked="false" Priority="73" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Grid Accent 2"/>
<w:LsdException Locked="false" Priority="60" SemiHidden="false" UnhideWhenUsed="false" Name="Light Shading Accent 3"/>
<w:LsdException Locked="false" Priority="61" SemiHidden="false" UnhideWhenUsed="false" Name="Light List Accent 3"/>
<w:LsdException Locked="false" Priority="62" SemiHidden="false" UnhideWhenUsed="false" Name="Light Grid Accent 3"/>
<w:LsdException Locked="false" Priority="63" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 1 Accent 3"/>
<w:LsdException Locked="false" Priority="64" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 2 Accent 3"/>
<w:LsdException Locked="false" Priority="65" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 1 Accent 3"/>
<w:LsdException Locked="false" Priority="66" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 2 Accent 3"/>
<w:LsdException Locked="false" Priority="67" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 1 Accent 3"/>
<w:LsdException Locked="false" Priority="68" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 2 Accent 3"/>
<w:LsdException Locked="false" Priority="69" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 3 Accent 3"/>
<w:LsdException Locked="false" Priority="70" SemiHidden="false" UnhideWhenUsed="false" Name="Dark List Accent 3"/>
<w:LsdException Locked="false" Priority="71" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Shading Accent 3"/>
<w:LsdException Locked="false" Priority="72" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful List Accent 3"/>
<w:LsdException Locked="false" Priority="73" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Grid Accent 3"/>
<w:LsdException Locked="false" Priority="60" SemiHidden="false" UnhideWhenUsed="false" Name="Light Shading Accent 4"/>
<w:LsdException Locked="false" Priority="61" SemiHidden="false" UnhideWhenUsed="false" Name="Light List Accent 4"/>
<w:LsdException Locked="false" Priority="62" SemiHidden="false" UnhideWhenUsed="false" Name="Light Grid Accent 4"/>
<w:LsdException Locked="false" Priority="63" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 1 Accent 4"/>
<w:LsdException Locked="false" Priority="64" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 2 Accent 4"/>
<w:LsdException Locked="false" Priority="65" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 1 Accent 4"/>
<w:LsdException Locked="false" Priority="66" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 2 Accent 4"/>
<w:LsdException Locked="false" Priority="67" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 1 Accent 4"/>
<w:LsdException Locked="false" Priority="68" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 2 Accent 4"/>
<w:LsdException Locked="false" Priority="69" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 3 Accent 4"/>
<w:LsdException Locked="false" Priority="70" SemiHidden="false" UnhideWhenUsed="false" Name="Dark List Accent 4"/>
<w:LsdException Locked="false" Priority="71" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Shading Accent 4"/>
<w:LsdException Locked="false" Priority="72" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful List Accent 4"/>
<w:LsdException Locked="false" Priority="73" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Grid Accent 4"/>
<w:LsdException Locked="false" Priority="60" SemiHidden="false" UnhideWhenUsed="false" Name="Light Shading Accent 5"/>
<w:LsdException Locked="false" Priority="61" SemiHidden="false" UnhideWhenUsed="false" Name="Light List Accent 5"/>
<w:LsdException Locked="false" Priority="62" SemiHidden="false" UnhideWhenUsed="false" Name="Light Grid Accent 5"/>
<w:LsdException Locked="false" Priority="63" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 1 Accent 5"/>
<w:LsdException Locked="false" Priority="64" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 2 Accent 5"/>
<w:LsdException Locked="false" Priority="65" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 1 Accent 5"/>
<w:LsdException Locked="false" Priority="66" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 2 Accent 5"/>
<w:LsdException Locked="false" Priority="67" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 1 Accent 5"/>
<w:LsdException Locked="false" Priority="68" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 2 Accent 5"/>
<w:LsdException Locked="false" Priority="69" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 3 Accent 5"/>
<w:LsdException Locked="false" Priority="70" SemiHidden="false" UnhideWhenUsed="false" Name="Dark List Accent 5"/>
<w:LsdException Locked="false" Priority="71" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Shading Accent 5"/>
<w:LsdException Locked="false" Priority="72" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful List Accent 5"/>
<w:LsdException Locked="false" Priority="73" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Grid Accent 5"/>
<w:LsdException Locked="false" Priority="60" SemiHidden="false" UnhideWhenUsed="false" Name="Light Shading Accent 6"/>
<w:LsdException Locked="false" Priority="61" SemiHidden="false" UnhideWhenUsed="false" Name="Light List Accent 6"/>
<w:LsdException Locked="false" Priority="62" SemiHidden="false" UnhideWhenUsed="false" Name="Light Grid Accent 6"/>
<w:LsdException Locked="false" Priority="63" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 1 Accent 6"/>
<w:LsdException Locked="false" Priority="64" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Shading 2 Accent 6"/>
<w:LsdException Locked="false" Priority="65" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 1 Accent 6"/>
<w:LsdException Locked="false" Priority="66" SemiHidden="false" UnhideWhenUsed="false" Name="Medium List 2 Accent 6"/>
<w:LsdException Locked="false" Priority="67" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 1 Accent 6"/>
<w:LsdException Locked="false" Priority="68" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 2 Accent 6"/>
<w:LsdException Locked="false" Priority="69" SemiHidden="false" UnhideWhenUsed="false" Name="Medium Grid 3 Accent 6"/>
<w:LsdException Locked="false" Priority="70" SemiHidden="false" UnhideWhenUsed="false" Name="Dark List Accent 6"/>
<w:LsdException Locked="false" Priority="71" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Shading Accent 6"/>
<w:LsdException Locked="false" Priority="72" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful List Accent 6"/>
<w:LsdException Locked="false" Priority="73" SemiHidden="false" UnhideWhenUsed="false" Name="Colorful Grid Accent 6"/>
<w:LsdException Locked="false" Priority="19" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Subtle Emphasis"/>
<w:LsdException Locked="false" Priority="21" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Intense Emphasis"/>
<w:LsdException Locked="false" Priority="31" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Subtle Reference"/>
<w:LsdException Locked="false" Priority="32" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Intense Reference"/>
<w:LsdException Locked="false" Priority="33" SemiHidden="false" UnhideWhenUsed="false" QFormat="true" Name="Book Title"/>
<w:LsdException Locked="false" Priority="37" Name="Bibliography"/>
<w:LsdException Locked="false" Priority="39" QFormat="true" Name="TOC Heading"/>
</w:LatentStyles>
</xml><![endif]--><style><!--
/* Font Definitions */
@font-face
    {font-family:Calibri;
    panose-1:2 15 5 2 2 2 4 3 2 4;
    mso-font-alt:"Arial Rounded MT Bold";
    mso-font-charset:0;
    mso-generic-font-family:swiss;
    mso-font-pitch:variable;
    mso-font-signature:-520092929 1073786111 9 0 415 0;}
@font-face
    {font-family:Tahoma;
    panose-1:2 11 6 4 3 5 4 4 2 4;
    mso-font-charset:0;
    mso-generic-font-family:swiss;
    mso-font-pitch:variable;
    mso-font-signature:-520081665 -1073717157 41 0 66047 0;}
@font-face
    {font-family:Verdana;
    panose-1:2 11 6 4 3 5 4 4 2 4;
    mso-font-alt:Verdana;
    mso-font-charset:0;
    mso-generic-font-family:swiss;
    mso-font-pitch:variable;
    mso-font-signature:-1593833729 1073750107 16 0 415 0;}
/* Style Definitions */
p.MsoNormal, li.MsoNormal, div.MsoNormal
    {mso-style-unhide:no;
    mso-style-qformat:yes;
    mso-style-parent:"";
    margin:0in;
    margin-bottom:.0001pt;
    mso-pagination:widow-orphan;
    font-size:12.0pt;
    font-family:"Times New Roman","serif";
    mso-fareast-font-family:Calibri;}
a:link, span.MsoHyperlink
    {mso-style-noshow:yes;
    mso-style-priority:99;
    color:blue;
    text-decoration:underline;
    text-underline:single;}
a:visited, span.MsoHyperlinkFollowed
    {mso-style-noshow:yes;
    mso-style-priority:99;
    color:purple;
    text-decoration:underline;
    text-underline:single;}
span.EmailStyle17
    {mso-style-type:personal-reply;
    mso-style-noshow:yes;
    mso-style-unhide:no;
    mso-ansi-font-size:10.0pt;
    mso-bidi-font-size:11.0pt;
    font-family:"Verdana","sans-serif";
    mso-ascii-font-family:Verdana;
    mso-hansi-font-family:Verdana;
    mso-bidi-font-family:"Times New Roman";
    color:#1F497D;
    font-weight:normal;
    font-style:normal;}
.MsoChpDefault
    {mso-style-type:export-only;
    mso-default-props:yes;
    font-family:"Calibri","sans-serif";
    mso-ascii-font-family:Calibri;
    mso-fareast-font-family:Calibri;
    mso-hansi-font-family:Calibri;
    mso-bidi-font-family:"Times New Roman";}
@page WordSection1
    {size:8.5in 11.0in;
    margin:1.0in 1.0in 1.0in 1.0in;
    mso-header-margin:.5in;
    mso-footer-margin:.5in;
    mso-paper-source:0;}
div.WordSection1
    {page:WordSection1;}
--></style><!--[if gte mso 10]><style>/* Style Definitions */
table.MsoNormalTable
    {mso-style-name:"Table Normal";
    mso-tstyle-rowband-size:0;
    mso-tstyle-colband-size:0;
    mso-style-noshow:yes;
    mso-style-priority:99;
    mso-style-parent:"";
    mso-padding-alt:0in 5.4pt 0in 5.4pt;
    mso-para-margin:0in;
    mso-para-margin-bottom:.0001pt;
    mso-pagination:widow-orphan;
    font-size:11.0pt;
    font-family:"Calibri","sans-serif";
    mso-ascii-font-family:Calibri;
    mso-hansi-font-family:Calibri;
    mso-bidi-font-family:"Times New Roman";}
</style><![endif]--><!--[if gte mso 9]><xml>
<o:shapedefaults v:ext="edit" spidmax="1026" />
</xml><![endif]--><!--[if gte mso 9]><xml>
<o:shapelayout v:ext="edit">
<o:idmap v:ext="edit" data="1" />
</o:shapelayout></xml><![endif]-->
</head>
<body lang="EN-US" link="blue" vlink="purple" style="tab-interval:.5in">
<div class="WordSection1">
<p class="MsoNormal"><span style="font-size:10.0pt;mso-bidi-font-size:11.0pt;font-family:&quot;Verdana&quot;,&quot;sans-serif&quot;;mso-bidi-font-family:&quot;Times New Roman&quot;;color:#1F497D">Thanks so much, Isaac!<o:p></o:p></span></p>
<p class="MsoNormal"><span style="font-size:10.0pt;mso-bidi-font-size:11.0pt;font-family:&quot;Verdana&quot;,&quot;sans-serif&quot;;mso-bidi-font-family:&quot;Times New Roman&quot;;color:#1F497D"><o:p>&nbsp;</o:p></span></p>
<p class="MsoNormal" style="text-autospace:none"><b><span style="font-size:10.0pt;font-family:&quot;Verdana&quot;,&quot;sans-serif&quot;;mso-fareast-font-family:&quot;Times New Roman&quot;;mso-bidi-font-family:&quot;Times New Roman&quot;;color:#1F497D;mso-no-proof:yes">Test Client Name<br>
Senior Manager, Digital Marketing<br>
Marketing Communications<br>
</span></b><span style="font-size:10.0pt;font-family:&quot;Verdana&quot;,&quot;sans-serif&quot;;mso-fareast-font-family:&quot;Times New Roman&quot;;mso-bidi-font-family:&quot;Times New Roman&quot;;color:#1F497D;mso-no-proof:yes">TEST CLIENT COMPANY<br>
101 N Wacker Drive, Chicago IL 60606<br>
(P) 800 872 9622 ext 6809&nbsp; (D) 312 419 6809<br>
(E) <a href="mailto:eunice.yi@example.net">eunice.yi@example.net</a>&nbsp;&nbsp; (W) example.net<o:p></o:p></span></p>
<p class="MsoNormal" style="text-autospace:none"><span style="font-size:10.0pt;font-family:&quot;Verdana&quot;,&quot;sans-serif&quot;;mso-fareast-font-family:&quot;Times New Roman&quot;;mso-bidi-font-family:&quot;Times New Roman&quot;;color:#1F497D;mso-no-proof:yes"><o:p>&nbsp;</o:p></span></p>
<p class="MsoNormal" style="text-autospace:none"><b><span style="font-size:10.0pt;font-family:&quot;Verdana&quot;,&quot;sans-serif&quot;;mso-fareast-font-family:&quot;Times New Roman&quot;;mso-bidi-font-family:&quot;Times New Roman&quot;;color:#1F497D;mso-no-proof:yes">The Y: We're for youth development,
 healthy living and social responsibility.</span></b><span style="font-size:10.0pt;font-family:&quot;Verdana&quot;,&quot;sans-serif&quot;;mso-fareast-font-family:&quot;Times New Roman&quot;;mso-bidi-font-family:&quot;Times New Roman&quot;;color:#1F497D;mso-no-proof:yes"><o:p></o:p></span></p>
<p class="MsoNormal"><span style="font-size:10.0pt;mso-bidi-font-size:11.0pt;font-family:&quot;Verdana&quot;,&quot;sans-serif&quot;;mso-bidi-font-family:&quot;Times New Roman&quot;;color:#1F497D"><o:p>&nbsp;</o:p></span></p>
<p class="MsoNormal"><b><span style="font-size:10.0pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;mso-fareast-font-family:&quot;Times New Roman&quot;">From:</span></b><span style="font-size:10.0pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;mso-fareast-font-family:&quot;Times New Roman&quot;"> system=metasushi.com@mg.example.com
 [mailto:system=metasushi.com@mg.example.com] <b>On Behalf Of </b>system@metasushi [dot] com<br>
<b>Sent:</b> Monday, May 02, 2016 4:06 PM<br>
<b>To:</b> Yi, Eunice<br>
<b>Subject:</b> Ticket Updated: #1144 All interior page are distorted on mobile<o:p></o:p></span></p>
<p class="MsoNormal"><o:p>&nbsp;</o:p></p>
<p class="MsoNormal">Message From Arion<br>
<b>Ticket Updated: #1144 All interior page are distorted on mobile</b> <br>
<br>
Item updated by Isaac Raway on May 02 at 4:05pm:<br>
<br>
<b>Comments</b> added:<o:p></o:p></p>
<div>
<p class="MsoNormal">Hi Eunice,<o:p></o:p></p>
</div>
<div>
<p class="MsoNormal">We're planning to do a deployment on the evening of Thursday 5/5, and this item should be included in that build.<o:p></o:p></p>
</div>
<p class="MsoNormal"><br>
<br>
<br>
<br>
<a href="https://tickets.example.com/items/tickets?id=1144">https://tickets.example.com/items/tickets?id=1144</a><br>
--<br>
Reply directly to this email, or use the URL above.<br>
<b>Hint:</b> When replying via email please delete your email signature! <o:p></o:p></p>
</div>
</body>
</html>
END;
}

