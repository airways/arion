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

class TextAreaField extends BaseDomainField implements IDomainField {

    /**
     * Called to render a field of this type.
     *
     * @param $field the field to render
     * @param $item the item to get data from
     */
	public function render($filterMode=false, $filterValue=false) {
        if($this->item->fieldHasConflicts($this->id) && $this->option('richText'))
        {
            $value = $this->item->getFieldConflicts($this->id)[0]->diff;
        } else {
            $value = $this->item->{$this->name};
        }

        // This prevents a bug with conflict resolution from stopping render from
        // working, also will alow converting a multitext field (messily) to a textarea.
        if(is_array($value)) {
            $newValue = '';
            foreach($value as $val) {
                if(is_array($val)) {
                    $newValue .= implode('<hr/>', $val).'<br>';
                } else {
                    $newValue .= $val.'<br>';
                }
            }
            $value = $newValue;
        }

        $vars = ['field' => $this,
                 'fieldName' => $this->getInputFieldName($filterMode),
                 'item' => $this->item,
                 'value' => $value];
        return $this->view->render('fields/textarea.html.twig', $vars);
	}


}
