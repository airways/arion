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

class EnumField extends BaseDomainField implements IDomainField {

    /**
     * Called to render a field of this type.
     */
	public function render($filterMode=false, $filterValue=false) {
        
        $options = (array)$this->option('options');

        $hideFilter = false;
        if($filterMode)
        {
            if(count($options) <= 1) $hideFilter = true;
            $options = array_merge(['' => 'any'], $options);
            foreach($options as $key => $label)
            {
                // TODO: Ticket #1122 -- Temporary hack to add a Not Closed option
                if($key=="closed") {
                    $options['not*'.$key] = '[Not '.$label.']';
                }
            }
            $this->item->{$this->name} = $filterValue;
        }

        // Render view of field
        $vars = ['filterMode' => $filterMode,
                 'field' => $this,
                 'fieldName' => $this->getInputFieldName($filterMode),
                 'item' => $this->item,
                 'value' => $this->item->{$this->name},
                 'options' => $options];
        //dump($vars['options']);exit;

        if($filterMode && $hideFilter) return '';
        else return $this->view->render('fields/enum.html.twig', $vars);
	}
    
    /**
     * Called to process data prior to saving a new item.
     */
    public function update($prevVer, array $data, array $files, array $cmd)
    {
        if($data[$this->name] == "_empty_")
        {
            $data[$this->name] = "";
        }
        
        return $data;
    }

    public function cssClass($i)
    {
        return 'col-xs-6 col-sm-6 col-md-4';
    }
}