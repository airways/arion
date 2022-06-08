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

namespace AppBundle\Fields;

use \Psr\Log\LoggerInterface;
use \Aws\S3\S3Client;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

use AppBundle\Service\AuthService;
use AppBundle\Entity\Field;
use AppBundle\Entity\Item;

class FileField extends BaseDomainField implements IDomainField {

    /**
     * @var Aws\S3\S3Client
     */
    protected $s3client;

    public function __construct(LoggerInterface $logger, EngineInterface $view, AuthService $session, S3Client $s3client)
    {
        parent::__construct($logger, $view, $session);
        $this->s3client = $s3client;
    }

    /**
     * Called to render a field of this type.
     *
     * @param $field the field to render
     * @param $item the item to get data from
     */
    public function render($filterMode=false, $filterValue=false) {
        list($values, $valuesMeta) = $this->getValuesAndMeta();

        // If the only item is blank, erase the array so we don't get an invalid file name
        if(count($values) == 1) {
            if(!$values[0]) {
                $values = [];
            }
        }
$this->logger->debug(__METHOD__.'::'.json_encode($values));
        $vars = ['field' => $this,
                 'item' => $this->item,
                 'values' => $values,
                 'valuesMeta' => $valuesMeta];
        return $this->view->render('fields/file.html.twig', $vars);
    }

    /**
     * Called to process data prior to saving a new item.
     */
    public function update($prevVer, array $data, array $files, array $cmd)
    {
        // check for existing files that have been saved previously
        $existingValues = $this->item->{$this->name};
        if(!is_array($existingValues)) $existingValues = [$existingValues];
        if(!is_array($existingValues[0])) $existingValues = [$existingValues];
            
        $data[$this->name] = $existingValues;
$this->logger->debug(__METHOD__.'::'.json_encode($data[$this->name]));
        if(array_key_exists($this->name, $cmd))
        {
            if(array_key_exists(0, $cmd))
            {
                foreach($cmd[$this->name][0] as $i => $command)
                {
                    if($command == 'delete') {
                        unset($data[$this->name][0][$i]);
                    }
                }
            }
        }

        // loop over array to get all new files
        $this->logger->debug(__METHOD__.'::files = '.print_r($files, true));

        if(isset($files[$this->name]))
        {
            $this->logger->debug(__METHOD__.sprintf('***** Found %d subfields!', count($files[$this->name])));
            foreach($files[$this->name] as $subFields)
            {
                $this->logger->debug(__METHOD__.sprintf('***** Found %d new files!', count($subFields)));
                foreach($subFields as $i => $fileData)
                {
                    // No file uploaded, continue on...
                    $this->logger->debug(__METHOD__.sprintf(':: process file %d class %s ********** ', $i, is_null($fileData) ? NULL : get_class($fileData)));
                    if(is_null($fileData)) continue;

                    // get filename
                    // strip non-alphanumeric chars from filename
                    $filename = preg_replace("/[^A-Za-z0-9_-]/", '', str_replace(' ', '_', $fileData->getClientOriginalName()));
                    $filename = substr($filename, 0, strlen($filename)-strlen($fileData->getClientOriginalExtension()));
                    // get the temp file on disk
                    $file = $fileData->getRealPath();
                    if($file)
                    {
                        $f = fopen($file, 'r');
                    } else {
                        $f = $fileData->getFd();
                    }
                    // get the file extension
                    $fileExtension = $fileData->guessExtension() ?: '';
                    // generate a unique key for S3
                    $key = $this->item->id.'/'.$filename.'.'.$fileExtension;
                    // upload file to S3 client
                    $result = $this->s3client->putObject([
                        'Bucket' => getenv('AWS_S3_BUCKET'),
                        'Key' => $key,
                        'Body' => $f
                        ]);
                    // update files data array with key
                    if($result) $data[$this->name][0][] = $key;
                }
            }
        } else {
            $this->logger->debug(__METHOD__.'***** No new files!');
        }

        return $data;
    }

}