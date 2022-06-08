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

namespace AppBundle\Fields\Meta;

use AppBundle\Entity\Field;
use AppBundle\Entity\Item;

/**
 * This interface is implemented by each field type class. Extra database calls needed
 * to prepare to render a field should be made in the load() method, while only basic
 * HTML generation and view rendering should be performed in the render() method.
 */
interface IFieldMeta {

	/**
     * Load extra data needed to render a form for this item
     */
    function load(Field $field);

}

