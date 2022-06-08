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

class TemplateField extends BaseDomainField implements IDomainField {

    /**
     * Called to render a field of this type.
     *
     * @param $field the field to render
     * @param $item the item to get data from
     */
    public function render($filterMode=false, $filterValue=false) {

        list($values, $valuesMeta) = $this->getValuesAndMeta();

        // Render view of field
        $vars = ['field' => $this,
                 'fieldName' => $this->getInputFieldName($filterMode),
                 'item' => $this->item,
                 'values' => $values,
                 'valuesMeta' => $valuesMeta];

        return $this->view->render('fields/template.html.twig', $vars);
    }
    
    /**
     * Called to process data prior to saving a new item.
     */
    public function update($prevVer, array $data, array $files, array $cmd)
    {
        
        return $data;
    }

}
