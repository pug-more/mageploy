<?php

/**
 * Description of Data
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Used to internally store current tracking status
     *
     * @var mixed
     */
    private $__track = null;

    /**
     * Used to internally store current username
     *
     * @var string
     */
    private $__user = null;

    /** @var  PugMoRe_Mageploy_Model_Io_File */
    protected $_io = null;

    /**
     * Set the tracking status;
     *
     * @param int $value 0 to disable, 1 to enable
     */
    private function __setActiveFlag($value)
    {
        $config = Mage::getModel('core/config');
        $config->saveConfig('dev/mageploy/active', $value);
        Mage::app()->cleanCache(array(Mage_Core_Model_Config::CACHE_TAG));
        $this->__track = $value;
    }

    /**
     * Set the current user name
     *
     * @param string $username
     */
    public function setUser($username)
    {
        $config = Mage::getModel('core/config');
        $config->saveConfig('dev/mageploy/user', $username);
        Mage::app()->cleanCache(array(Mage_Core_Model_Config::CACHE_TAG));
        $this->__user = $username;
    }

    /**
     * Get current Mageploy version from config.xml;
     *
     * @param type $len if a value is passed, return a portion of the version
     * numbers counting from the first one up to $len
     *
     * @return string
     */
    public function getVersion($len = null)
    {
        $ver = Mage::getConfig()->getNode('modules/PugMoRe_Mageploy/version');
        if (is_numeric($len)) {
            $numbers = explode('.', $ver);
            if ($len < count($numbers)) {
                $ver = implode('.', array_slice($numbers, 0, $len));
            }
        }
        return $ver;
    }

    public function log($msg)
    {
        $args = func_get_args();
        $formattedMsg = call_user_func_array('sprintf', $args);
        Mage::log($formattedMsg, null, 'PugMoRe_Mageploy.log', Mage::getStoreConfigFlag('dev/mageploy/debug'));
    }

    public function isActive()
    {
        if (is_null($this->__track)) {
            $this->__track = Mage::getStoreConfigFlag('dev/mageploy/active');
        }
        return $this->__track;
    }

    public function disable()
    {
        $this->__setActiveFlag(0);
        return false;
    }

    public function enable()
    {
        $this->__setActiveFlag(1);
        return true;
    }

    public function getStoragePath()
    {
        return Mage::getBaseDir() . DS . 'var' . DS . 'mageploy' . DS;
    }

    public function getUser()
    {
        if (is_null($this->__user)) {
            $this->__user = Mage::getStoreConfig('dev/mageploy/user');
        }
        return $this->__user;
    }

    public function isAnonymousUser() {
        return !strcmp('anonymous', $this->getUser());
    }

    public function getExecutedActionsFilename()
    {
        return 'mageploy_executed.csv';
    }

    public function getAllActionsFilename()
    {
        return 'mageploy_all.csv';
    }

    public function getAllActionsCount()
    {
        if (is_null($this->_io)) {
            $this->_io = new PugMoRe_Mageploy_Model_Io_File();
        }
        return count($this->_io->getHistoryList());
    }

    public function getPendingActionsCount()
    {
        if (is_null($this->_io)) {
            $this->_io = new PugMoRe_Mageploy_Model_Io_File();
        }
        return count($this->_io->getPendingList());
    }

    public function getAttributeIdFromCode($attributeCode, $entityTypeId)
    {
        $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setCodeFilter($attributeCode)
            ->setEntityTypeFilter($entityTypeId)
            ->getFirstItem();
        return $attributeInfo->getId();
    }

    public function getAttributeCodeFromId($attributeId)
    {
        $attribute = Mage::getModel('eav/entity_attribute')
            ->load($attributeId);
        return $attribute->getAttributeCode();
    }

    public function getAttributeSetId($attributeSetName, $entityTypeCode)
    {
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

    public function getAttributeSetIdFromName($attributeSetName)
    {
        return $this->getAttributeSetId($attributeSetName, 'catalog_product');
    }

    public function getEntityTypeCodeFromId($entityTypeId)
    {
        $entityType = Mage::getModel('eav/entity_type')->load($entityTypeId);
        return $entityType->getEntityTypeCode();
    }

    public function getEntityTypeIdFromCode($entityTypeCode)
    {
        $entityType = Mage::getModel('eav/entity_type')->loadByCode($entityTypeCode);
        return $entityType->getEntityTypeId();
    }

    public function getAttributeSetNameById($attributeSetId)
    {
        $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
        return $attributeSet->getAttributeSetName();
    }

    public function getEavEntityAttributeRow($entityAttributeId)
    {
        $res = Mage::getSingleton('core/resource');
        $conn = $res->getConnection('core_read');
        $select = $conn
            ->select()
            ->where('entity_attribute_id=?', $entityAttributeId)
            ->from($res->getTableName('eav/entity_attribute'));
        return $conn->fetchRow($select);
    }

    public function getEavEntityAttributeId($entityTypeId, $attributeSetId, $attributeGroupId, $attributeId)
    {
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

    public function getEntityAttribute($attributeCode, $groupId)
    {
        $entityAttribute = Mage::getResourceModel('eav/entity_attribute_collection')
            ->setCodeFilter($attributeCode)
            ->setAttributeGroupFilter($groupId)
            ->getFirstItem();

        return $entityAttribute;
    }

    public function getAttributeGroupId($attributeSetId, $attributeGroupName)
    {
        $attributeGroup = Mage::getResourceModel('eav/entity_attribute_group_collection')
            ->setAttributeSetFilter($attributeSetId)
            ->addFieldToFilter('attribute_group_name', $attributeGroupName)
            ->getFirstItem();

        return $attributeGroup->getId();
    }

    public function getCategoryUuidFromPath($path, $append = null, $separator)
    {
        $categoryUuidParts = array();
        $categoryIdParts = explode('/', $path);
        foreach ($categoryIdParts as $i => $catId) {
            if ($i == 0) {
                $categoryUuidParts[] = Mage_Catalog_Model_Category::TREE_ROOT_ID;
            } else {
                $categoryUuidParts[] = Mage::getModel('catalog/category')->load($catId)->getName();
            }
        }
        if ($append) {
            $categoryUuidParts[] = $append;
        }
        return implode($separator, $categoryUuidParts);
    }

    public function getCategoryPathFromUuid($uuid, $separator) {
        $pathParts = array();
        $categoryPathParts = explode($separator, $uuid);
        foreach ($categoryPathParts as $i => $catUuid) {
            if ($i == 0) {
                $pathParts[] = Mage_Catalog_Model_Category::TREE_ROOT_ID;
            } else {
                $category = $this->_getCategoryFromParentIdAndName($pathParts[$i - 1], $catUuid);
                $pathParts[] = $category->getId();
            }
        }
        return implode('/', $pathParts);
    }

    protected function _getCategoryFromParentIdAndName($parentId, $name) {
        return Mage::getModel('catalog/category')
            ->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('parent_id', $parentId)
            ->addFieldToFilter('name', $name)
            ->getFirstItem();
    }

    public function getCategoryIdFromPath($path) {
        return substr($path, strrpos($path, '/') + 1);
    }

}
