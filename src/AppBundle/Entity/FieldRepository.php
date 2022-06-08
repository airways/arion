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


namespace AppBundle\Entity;

use Psr\Log\LoggerInterface;

/**
 * FieldRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class FieldRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    protected $logger;

    public function initService(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Find fields for a particular item type
     *
     * @param integer $accountId
     * @param integer $itemTypeId
     */
    /*
    public function findFieldsForItemType($accountId, $itemTypeId)
    {
        $this->logger->debug(__METHOD__.'::'.json_encode(['accountId' => $accountId, 'itemTypeId' => $itemTypeId]));
        if(!$accountId) throw new \InvalidArgumentException('findFieldsForItemType requires accountId');
        if(!$itemTypeId) throw new \InvalidArgumentException('findFieldsForItemType requires itemTypeId');

        return $this->findBy(['accountId' => $accountId,
                              'itemType' => $itemTypeId]);

    }
    */

    /**
     * Find an individual field by its ID, within the given account.
     *
     * @param integer $accountId account to search within
     * @param integer $fieldId field ID to find
     * @return Field_model
     */
    public function getField($accountId, $fieldId)
    {
        return $this->findOneBy(['accountId' => $accountId,
                                 'id' => $fieldId]);
    }

    /**
     * Create a new field
     *
     * @param integer $accountId account the item type should belond to
     * @param integer $itemTypeId to create new field under
     * @param string name for the new field
     * @param string label for the new field
     * @param string fieldType for the new field
     * @param bool inTitle for the new field
     * @param object fieldOptions for the new field
     * @return \app\models\Field
     */
    public function createField($accountId, $itemTypeId, $name, $label, $fieldType, $inTitle, $fieldOptions)
    {
        $field = new Field();
        $field->setAccountId($accountId);
        $field->setItemTypeId($itemTypeId);
        $field->setName($name);
        $field->setLabel($label);
        $field->setFieldType($fieldType);
        $field->setInTitle($inTitle);
        if(is_object($fieldOptions)) $fieldOptions = json_encode($fieldOptions);
        $field->setFieldOptions($fieldOptions);
        if($field->save()) {
            $field = $this->getField($accountId, $field->getId());
            return $field;
        }
    }

    /**
     * Find a relationship field that points to a particular item, with own_users flag on it's item type
     */
    public function getOwnerField ($accountId, $targetItemId)
    {
        /*
        return $this->->where('fields.account_id', '=', $accountId)
                                    ->where('fields.field_type', '=', 'Relationship')
                                    ->join('item_types', 'item_types.id', '=', 'fields.item_type_id')
                                    ->where('item_types.own_users', '=', 1)
                                    ->join('item_values', 'item_values.field_id', '=', 'fields.id')
                                    ->where('item_values.value', '=', $targetItemId)
                                    ->all();
        */

        $sql = "SELECT * FROM fields ".
                "JOIN item_types ON item_types.id = fields.item_type_id ".
                "JOIN item_values On item_values.field_id = fields.id ".
                "WHERE fields.account_id = :accountId AND fields.field_type = 'Relationship' AND ".
                "      AND item_types.own_users = 1 AND item_values.value = :targetItemId";
        $rsm = new ResultSetMapping();
        $query = $this->doctrine->getEntityManager()->createNativeQuery($sql, $rsm);
        $query->setParameter('accountId', $accountId);
        $query->setParameter('targetItemId', $targetItemId);

        if(count($query) > 0)
        {
            return $query[0];
        }
        return NULL;
    }
}
