<?php

/**
 * Description of Data
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Helper_Data extends Mage_Core_Helper_Abstract {

    public function getStoragePath() {
        return Mage::getBaseDir() . DS . 'var' . DS . 'log' . DS;
    }

    public function getExecutedActionsFilename() {
        return 'mageploy_executed.csv';
    }

    public function getAllActionsFilename() {
        return 'mageploy_all.csv';
    }

    public function getAttributeIdFromCode($attributeCode) {
        $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setCodeFilter($attributeCode)
                ->getFirstItem();
        return $attributeInfo->getId();
    }

    public function getAttributeCodeFromId($attributeId) {
        $attribute = Mage::getModel('eav/entity_attribute')
                ->load($attributeId);
        return $attribute->getAttributeCode();
    }

    public function getAttributeSetIdFromName($attributeSetName) {
        $entityTypeId = Mage::getModel('eav/entity')
                ->setType('catalog_product')
                ->getTypeId();
        $attributeSet = Mage::getModel('eav/entity_attribute_set')
                ->getCollection()
                ->setEntityTypeFilter($entityTypeId)
                ->addFieldToFilter('attribute_set_name', $attributeSetName)
                ->getFirstItem();
        return $attributeSet->getAttributeSetId();
    }

    public function getEntityTypeCodeFromId($entityTypeId) {
        $entityType = Mage::getModel('eav/entity_type')->load($entityTypeId);
        return $entityType->getEntityTypeCode();
    }

    public function getEntityTypeIdFromCode($entityTypeCode) {
        $entityType = Mage::getModel('eav/entity_type')->loadByCode($entityTypeCode);
        return $entityType->getEntityTypeId();
    }

    public function getAttributeSetNameById($attributeSetId) {
        $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
        return $attributeSet->getAttributeSetName();
    }

    public function getEavEntityAttributeRow($entityAttributeId) {
        $res = Mage::getSingleton('core/resource');
        $conn = $res->getConnection('core_read');
        $select = $conn
                ->select()
                ->where('entity_attribute_id=?', $entityAttributeId)
                ->from($res->getTableName('eav/entity_attribute'));
        return $conn->fetchRow($select);
    }

    public function getEntityAttribute($attributeCode, $groupId) {
        $entityAttribute = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setCodeFilter($attributeCode)
                ->setAttributeGroupFilter($groupId)
                ->getFirstItem();
        
        return $entityAttribute;
    }

}
