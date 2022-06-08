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

namespace AppBundle\Controller;

use Psr\Log\LoggerInterface;
use \Aws\S3\S3Client;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use AppBundle\Service\FileService;

/**
 * @Route(service="app.file_controller")
 */
class Files extends BaseController
{
    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;
    
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    protected $router;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    protected $view;

    /**
     * @var Aws\S3\S3Client
     */
    protected $s3client;

    /**
     * @var AppBundle\Service\FileService;
     */
    protected $fileService;

    public function __construct(LoggerInterface $logger, RouterInterface $router, EngineInterface $view,
                                S3Client $s3client, FileService $fileService)
    {
        $this->logger = $logger;
        $this->router = $router;
        $this->view = $view;
        $this->s3client = $s3client;
        $this->fileService = $fileService;
    }

    /**
     * List files
     *
     * @Route("/files", name="files_index")
     */
    public function index(Request $request)
    {
        $result = $this->fileService->listing();
        if(($res = $this->checkResult($request, $result)) !== 0) return $res;

        $vars = (array)$result;
        $vars['pageTitle'] = 'Files';
        return $this->view->renderResponse('files/index.html.twig', $vars);
    }

    /**
     * View the file
     *
     * @param integer $itemId
     * @param string $file
     *
     * @Route("/files/{itemId}/{file}", name="files")
     */
    public function view(Request $request, $itemId, $file)
    {
        $file = str_replace('.jpg', '.jpeg', $file);
        $key = $itemId.'/'.$file;
        $this->logger->debug(__METHOD__.'::params'.json_encode([$key]));

        // @TODO: check for permission to view
        $result = $this->s3client->getObject([
            'Bucket' => getenv('AWS_S3_BUCKET'),
            'Key' => $key
        ]) ;

        if($result){
            $contentType = $result['ContentType'];
            if($contentType == 'application/octet-stream')
            {
                
                $info = new \SplFileInfo($file);
                switch(strtolower($info->getExtension()))
                {
                    case 'jpg':
                    case 'jpeg':
                        $contentType = 'image/jpeg';
                        break;
                    case 'png':
                        $contentType = 'image/png';
                        break;
                    case 'gif':
                        $contentType = 'image/gif';
                        break;
                    case 'pdf':
                        $contentType = 'application/pdf';
                        break;
                }
            }
            header('Content-Type: '.$contentType);
            header('Content-Length: '.$result['ContentLength']);
            header('Content-Disposition: inline; filename="'.$file.'"');
            echo $result['Body'];
            exit;
        }
        
    }
}
