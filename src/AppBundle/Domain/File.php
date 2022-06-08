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

namespace AppBundle\Domain;

use Psr\Log\LoggerInterface;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use AppBundle\Service\AuthService;

class File {
    use ContainerAwareTrait;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    
    /**
     * @var \AppBundle\Entity\ItemValue
     */
    protected $modelItemValue;

    protected $itemTitle = '';
    protected $itemTypePluralName = '';

    public function __construct(LoggerInterface $logger, 
                                \AppBundle\Entity\ItemValue $modelItemValue, array $itemInfo) {
        $this->logger = $logger;

        $this->modelItemValue = $modelItemValue;
        $this->itemTitle = $itemInfo['title'];
        $this->itemTypePluralName = $itemInfo['plural_name'];
    }

    public function getFileName()
    {
        return $this->modelItemValue->getValue();
    }

    public function getCreatedAt()
    {
        return $this->modelItemValue->getCreatedAt();
    }

    public function getItemId()
    {
        return $this->modelItemValue->getItemId();
    }

    public function getItemTitle()
    {
        return $this->itemTitle;
    }

    public function getItemTypePluralName()
    {
        return $this->itemTypePluralName;
    }

    public function getGlyph()
    {
        $info = new \SplFileInfo($this->getFileName());
        switch($info->getExtension())
        {
            case 'jpg':
            case 'jpeg':
            case 'gif':
            case 'png':
            case 'bmp':
                return 'picture';
                break;

            case 'xls':
            case 'xlsx':
                return 'th';
                break;

            case 'pdf':
            case 'doc':
            case 'docx':
                return 'file';
                break;

            case 'ppt':
            case 'pptx':
                return 'blackboard';
                break;

            case 'zip':
                return 'folder-open';
                break;

            default:
                return 'file';
        }
    }
}
