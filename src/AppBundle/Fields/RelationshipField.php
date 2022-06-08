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


class RelationshipField extends BaseDomainField implements IDomainField {

    /**
     * Called to render a field of this type.
     *
     * @param $field the field to render
     * @param $item the item to get data from
     */
    public function render($filterMode=false, $filterValue=false) {
        // Get data saved for us by RelationshipMeta->loadMeta()
        $itemOptions = $this->fieldModel->getMeta()->getItemOptions();

        $hideFilter = false;
        if($filterMode)
        {
            if(count($itemOptions) <= 1) $hideFilter = true;
            $this->item->{$this->name} = $filterValue;
        }

        // Render view of field
        $vars = ['filterMode' => $filterMode,
                 'field' => $this,
                 'fieldName' => $this->getInputFieldName($filterMode),
                 'item' => $this->item,
                 'itemOptions' => $itemOptions,
                 'value' => $this->item->{$this->name},
                 'noneLabel' => $filterMode ? 'any' : '-None-'];
        
        if($filterMode && $hideFilter) return '';
        else return $this->view->render('fields/relationship.html.twig', $vars);
    }

    public function cssClass($i)
    {
        return 'col-xs-6 col-sm-6 col-md-4';
    }
}
