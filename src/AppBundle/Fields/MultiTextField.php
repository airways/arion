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

class MultiTextField extends BaseDomainField implements IDomainField {

    /**
     * Called to render a field of this type.
     *
     * @param $field the field to render
     * @param $item the item to get data from
     */
    public function render($filterMode=false, $filterValue=false) {

        list($values, $valuesMeta) = $this->getValuesAndMeta();
        // DEBUG $this->logger->debug(__METHOD__.'::'.json_encode($values));
        // DEBUG if(!is_array($values[0]))
        // DEBUG {
        // DEBUG    $this->logger->debug(__METHOD__.'::first index is not an array!');
        // DEBUG }

        if(!$this->option('editable') && count($values) > 0)
        {
            // When adding subValues we will add 1 to the index created by the view,
            // see loop in update() below
            array_shift($values[0]);
            array_shift($valuesMeta[0]);
        }

        // Render view of field
        $vars = ['field' => $this,
                 'item' => $this->item,
                 'values' => $values,
                 'valuesMeta' => $valuesMeta];

        switch($this->option('style', 'text'))
        {
            case 'text':
                return $this->view->render('fields/multitext.html.twig', $vars);
                break;
            case 'textarea':
                return $this->view->render('fields/multitextarea.html.twig', $vars);
                break;
        }
    }
    
    /**
     * Called to process data prior to saving a new item.
     */
    public function update($prevVer, array $data, array $files, array $cmd)
    {
        // TODO: if the field is not visibleToRestrictedUsers and the current user is restricted,
        // we also need to do the following to preserve existing values
        // TODO: this really needs to be moved into a generic place so that any field that is
        // missing values because it isn't editable or because of permissions is not erased
        if(!$this->option('editable')) {
            list($values, $valuesMeta) = $this->getValuesAndMeta();

            // Array returned will NOT have existing rows included, but they
            // cannot be modified or removed, so prepend them to the data to save
            $existingValues = $this->item->{$this->name};
            if(!is_array($existingValues)) $existingValues = [$existingValues];
            if(!is_array($existingValues[0])) $existingValues = [$existingValues];
            if(isset($data[$this->name]))
            {
                $newValues = $data[$this->name];
                $data[$this->name] = $existingValues;


                // If data is coming from the render()'d view then the initial blank string has
                // already been trimmed off, so we need to offset indices by 1, otherwise
                // we don't need to.
                if(is_array($newValues) && is_array($newValues[0]) && isset($newValues[0][0]) && $newValues[0][0] == '') $n = 0;
                else $n = 1;
                if(isset($newValues[0]))
                {
                    foreach($newValues[0] as $i => $value)
                    {

                        // No blank rows allowed
                        if($value)
                        {

                            // Index is off by one because initial subValue is always blank, and is trimmed off
                            // in render() above so it does not render a blank row in the view
                            
                            // Ensure user should be allowed to overwrite the value -- only allow creating
                            // new rows, or editing rows they recently posted:
                            // Timelimit in UI is 2 minutes (after which value won't appear to be editable on reload),
                            // give users 15 minutes total to realize a mistake was made an submit the edit
                            $this->logger->debug(__METHOD__.'::check permissions for index '.($i+$n));
                            if(!array_key_exists($i+$n, $valuesMeta) || (
                                $valuesMeta[$i+$n]->createdByUserId == $this->session->getUserId() &&
                                 $valuesMeta[$i+$n]->createdAt >= time() - 60*15 ))
                            {
                                $this->logger->debug(__METHOD__.'::permissions OK for index '.($i+$n));
                                $data[$this->name][0][$i+$n] = $value;
                            } else {
                                $this->logger->debug(__METHOD__.'::permission DENIED for index '.($i+$n));
                            }
                        }
                    }

                }

                // echo '<b>Existing</b>';
                // var_dump($existingValues);
                // echo '<b>New</b>';
                // var_dump($newValues);
                // echo '<b>Result</b>';
                // var_dump($data[$this->name]);
                // exit;


                //$data[$this->name] = array_merge($existingValues, $newValues);
            }
        }
        // DEBUG $this->logger->debug(__METHOD__.'::'.json_encode($data[$this->name]));
        return $data;
    }

}
