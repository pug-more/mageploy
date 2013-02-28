<?php

/**
 * Description of Data
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Helper_Data extends Mage_Core_Helper_Abstract {
    
    private function __setActiveFlag($value) {
        $config = Mage::getModel('core/config');
        $config->saveConfig('dev/mageploy/active', $value);
        Mage::app()->cleanCache(array(Mage_Core_Model_Config::CACHE_TAG));
    }
    
    public function log($msg) {
        $args = func_get_args();
        $formattedMsg = call_user_func_array('sprintf', $args);
        Mage::log($formattedMsg, null, 'PugMoRe_Mageploy.log', Mage::getStoreConfigFlag('dev/mageploy/debug'));
    }
            
    
    public function isActive() {
        return Mage::getStoreConfigFlag('dev/mageploy/active');
    }
    
    public function disable() {
        $this->__setActiveFlag(0);
        return false;
    }
    
    public function enable() {
        $this->__setActiveFlag(1);
        return true;
    }

    public function getStoragePath() {
        return Mage::getBaseDir() . DS . 'var' . DS . 'mageploy' . DS;
    }
    
    public function getUser() {
        return Mage::getStoreConfig('dev/mageploy/user');
    }

    public function getExecutedActionsFilename() {
        return 'mageploy_executed.csv';
    }

    public function getAllActionsFilename() {
        return 'mageploy_all.csv';
    }

    public function getAttributeIdFromCode($attributeCode, $entityTypeId) {
        $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setCodeFilter($attributeCode)
                ->setEntityTypeFilter($entityTypeId)
                ->getFirstItem();
        return $attributeInfo->getId();
    }

    public function getAttributeCodeFromId($attributeId) {
        $attribute = Mage::getModel('eav/entity_attribute')
                ->load($attributeId);
        return $attribute->getAttributeCode();
    }

    public function getAttributeSetId($attributeSetName, $entityTypeCode) {
        $entityTypeId = Mage::getModel('eav/entity')
                ->setType($entityTypeCode)
                ->getTypeId();
        $attributeSet = Mage::getModel('eav/entity_attribute_set')
                ->getCollection()
                ->setEntityTypeFilter($entityTypeId)
                ->addFieldToFilter('attribute_set_name', $attributeSetName)
                ->getFirstItem();
        return $attributeSet->getAttributeSetId();
    }

    public function getAttributeSetIdFromName($attributeSetName) {
        return $this->getAttributeSetId($attributeSetName, 'catalog_product');
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

    public function getEavEntityAttributeId($entityTypeId, $attributeSetId, $attributeGroupId, $attributeId) {
        $res = Mage::getSingleton('core/resource');
        $conn = $res->getConnection('core_read');
        $select = $conn
                ->select()
                ->where('entity_type_id=?', $entityTypeId)
                ->where('attribute_set_id=?', $attributeSetId)
                ->where('attribute_group_id=?', $attributeGroupId)
                ->where('attribute_id=?', $attributeId)
                ->from($res->getTableName('eav/entity_attribute'));
        $row = $conn->fetchRow($select);
        return $row['entity_attribute_id'];
    }

    public function getEntityAttribute($attributeCode, $groupId) {
        $entityAttribute = Mage::getResourceModel('eav/entity_attribute_collection')
                ->setCodeFilter($attributeCode)
                ->setAttributeGroupFilter($groupId)
                ->getFirstItem();

        return $entityAttribute;
    }

    public function getAttributeGroupId($attributeSetId, $attributeGroupName) {
        $attributeGroup = Mage::getResourceModel('eav/entity_attribute_group_collection')
                ->setAttributeSetFilter($attributeSetId)
                ->addFieldToFilter('attribute_group_name', $attributeGroupName)
                ->getFirstItem();
        
        return $attributeGroup->getId();
    }
 
}
