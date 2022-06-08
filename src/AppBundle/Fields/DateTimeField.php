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

class DateTimeField extends BaseDomainField implements IDomainField {

    /**
     * Called to render a field of this type.
     *
     * @param $field the field to render
     * @param $item the item to get data from
     */
    public function render($filterMode=false, $filterValue=false) {
        $vars = ['field' => $this,
                 'fieldName' => $this->getInputFieldName($filterMode),
                 'item' => $this->item,
                 'value' => $this->item->{$this->name}];

        $fieldId = str_replace('[', '_', str_replace(']', '', $this->getInputFieldName($filterMode)));
        $dateTimePicker = new \Kendo\UI\DatePicker($fieldId);
        $dateTimePicker->attr('name', $this->getInputFieldName($filterMode));
        $dateTimePicker->value($this->item->{$this->name});
        $dateTimePicker->format("yyyy-MM-dd");
        //$dateTimePicker->format("yyyy-MM-dd h:mm tt");

        // Configure it
        //$dateTimePicker->animation(true);

        // Output it
        return $dateTimePicker->render();

/*.<<<EOF

<script>
$(document).ready(function() {
var {$fieldId} = $("#{$fieldId}").data("kendoTimePicker");
{$fieldId}.min("8:00 AM");
{$fieldId}.max("6:00 PM");
});
</script>
EOF;*/

        //return $this->view->render('fields/datetime.html.twig', $vars);
    }

    public function cssClass($i)
    {
        return 'col-xs-6 col-sm-6 col-md-4';
    }

}