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

class GridField extends BaseDomainField implements IDomainField {
    // Some conf keys for columns map to different methods
    private static $columnConfs = ['name' => 'field', 'label' => 'title'];

    /**
     * Called to render a field of this type.
     *
     * @param $field the field to render
     * @param $item the item to get data from
     */
    public function render($filterMode=false, $filterValue=false) {
        $value = $this->item->{$this->name};
        //var_dump($value);
        $value = json_decode($value);

        $fieldId = str_replace('[', '_', str_replace(']', '', $this->getInputFieldName($filterMode)));
        $fieldName = $this->getInputFieldName($filterMode);

        $grid = new \Kendo\UI\Grid($fieldId);
        $grid->attr('data-field-name', $fieldName);

        // Get column configuration JSON object
        $columnConfigs = $this->option('columns');

        // Loop over configs and fill in these
        $schema = new \Kendo\Data\DataSourceSchema();
        $aggregates = [];
        $defaultRow = [];

        foreach($columnConfigs as $columnConfig)
        {
            $column = new \Kendo\UI\GridColumn();

            if(!isset($columnConfig->template)
                || isset($columnConfig->type))
            {
                $defaultRow[$columnConfig->name] = '';
            }

            $costFunctions=[];
            // Map conf keys to method calls on the column
            foreach($columnConfig as $key => $confValue)
            {
                if(isset(self::$columnConfs[$key]))
                {
                    $key = self::$columnConfs[$key];
                }
                if(method_exists($column, $key))
                {
                    //echo 'call '.$key.'<br>';
                    $column->$key($confValue);
                }

                switch($key)
                {
                    case 'function':
                        // @TODO: replace with parser.js call to make expressions safe
                        $jsFunction = new \Kendo\JavaScriptFunction('function() { return '.$confValue.'; }');
                        //$schema->
                        break;
                    case 'aggregates':
                        foreach($confValue as $confSubValue)
                        {
                            $aggregate = new \Kendo\Data\DataSourceAggregateItem();
                            $aggregate->field($columnConfig->name)
                                      ->aggregate($confSubValue);
                            $aggregates[] = $aggregate;
                        }
                        break;
                    case 'type':
                        switch($confValue)
                        {
                            case 'text':
                                $column->editor('main.textareaEditor');
                                $column->encoded(false);
                                $column->template('#=typeof('.$columnConfig->name.') != "undefined" ? kendo.toString(main.cellEncoding('.$columnConfig->name.')):""#');
                            break;
                            case 'cost':
                                $column->template('#="$"+'.$columnConfig->name.'().format()#');
                                $column->footerTemplate('#=main.sumColumn("'.$fieldId.'", "'.$columnConfig->costColumn.'", '.$columnConfig->costPrice.')#');
                                $costFunctions[] = $columnConfig->name.': function () {return this.'.$columnConfig->costColumn.' * '.$columnConfig->costPrice.';}';
                            break;
                        }
                        break;
                }
            }

            $grid->addColumn($column);
        }

        if(!$value) $value = [$defaultRow];

        $dataSource = new \Kendo\Data\DataSource();
        $dataSource->data($value);
        foreach($aggregates as $aggregate)
        {
            $dataSource->addAggregateItem($aggregate);
        }


        
        $grid->dataSource($dataSource)
             ->editable(['createAt' => 'bottom'])
             ->pageable(false)
             ->addToolbarItem(new \Kendo\UI\GridToolbarItem('create'));

        $vars = ['field' => $this,
                 'fieldId' => $fieldId,
                 'fieldName' => $fieldName,
                 'item' => $this->item,
                 'value' => $this->item->{$this->name},
                 'json' => $grid->toJSON(),
                 'costFunctions' => implode(',', $costFunctions),
                 'selector' => preg_replace('/([\[\]])/', "\\\\\\\\\\1", $fieldId),
                ];

        return $this->view->render('fields/grid.html.twig', $vars);
    }

    /**
     * Called to process data prior to saving a new item.
     */
    public function update($prevVer, array $data, array $files, array $cmd)
    {
        // var_dump($data);
        // echo 'gridfield -> update';
        // exit;
        $data[$this->name] = $data[$this->name];
        return $data;
    }

}