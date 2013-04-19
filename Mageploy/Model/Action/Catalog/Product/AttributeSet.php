<?php

/**
 * Description of Attribute Set
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Catalog_Product_AttributeSet extends PugMoRe_Mageploy_Model_Action_Abstract {

    const VERSION = '1';
    
    protected $_code = 'catalog_product_attributeSet';
    protected $_blankableParams = array('id', 'key', /*'isAjax',*/ 'form_key');

    protected function _getVersion() {
        return Mage::helper('pugmore_mageploy')->getVersion(2).'.'.self::VERSION;
    }
    
    protected function _getEntityTypeCode() {
        return Mage_Catalog_Model_Product::ENTITY;
    }

    protected function _getEntityTypeId() {
        return Mage::helper('pugmore_mageploy')->getEntityTypeIdFromCode($this->_getEntityTypeCode());
    }

    public function match() {
        if (!$this->_request) {
            return false;
        }

        if ($this->_request->getModuleName() == 'admin') {
            if ($this->_request->getControllerName() == 'catalog_product_set') {
                if (in_array($this->_request->getActionName(), array(/* 'validate', */'save', 'delete'))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function encode() {
        $result = parent::encode();

        if ($this->_request) {
            $params = $this->_request->getParams();

            $newOrExisting = '';
            if (isset($params['attribute_set_name'])) {
                // new entity
                $params['mageploy_uuid'] = $params['attribute_set_name'];
                $newOrExisting = 'new';
            } else {
                // existing entity
                $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($params['id']);
                $params['mageploy_uuid'] = $attributeSet->getAttributeSetName();
                $newOrExisting = 'existing';
            }

            if (isset($params['skeleton_set'])) {
                $skeletonSet = Mage::getModel('eav/entity_attribute_set')->load($params['skeleton_set']);
                $params['skeleton_set'] = $skeletonSet->getAttributeSetName();
            }

            foreach ($this->_blankableParams as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                }
            }

            // json decode $params['data']
            $data = Mage::helper('core')->jsonDecode($params['data']);
            if (isset($data['form_key'])) {
                unset($data['form_key']);
            }

            $helper = Mage::helper('pugmore_mageploy');

            // $data['attributes'] contains the following
            // eav_entity_attribute table values
            //
            // [0] attribute_id
            // [1] attribute_group_id
            // [2] sort_order
            // [3] eav_attribute_id (empty if it's a new association)
            foreach ($data['attributes'] as $i => $attribute) {
                $attributeId = $attribute[0];
                $attributeGroupId = $attribute[1];
                $eavAttributeId = $attribute[3];

                $attributeUuid = $helper->getAttributeCodeFromId($attributeId);

                $entityAttribute = $helper->getEntityAttribute($attributeUuid, $attributeGroupId);

                $attributeSetId = $entityAttribute->getAttributeSetId();
                $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
                $attributeSetUuid = $attributeSet->getAttributeSetName();

                if (!strncmp($attributeGroupId, 'ynode', strlen('ynode'))) {
                    $attributeGroupUuid = $attributeGroupId;
                } else {
                    $attributeGroup = Mage::getModel('eav/entity_attribute_group')->load($attributeGroupId);
                    $attributeGroupUuid = $attributeGroup->getAttributeGroupName()
                            . self::UUID_SEPARATOR . $attributeSetUuid;
                }

                $eavAttributeUuid = $attributeGroupUuid
                        . self::UUID_SEPARATOR . $attributeUuid;

                // Convert IDs into UUIDs
                $data['attributes'][$i][0] = $attributeUuid;
                $data['attributes'][$i][1] = $attributeGroupUuid;
                if (!empty($data['attributes'][$i][3])) {
                    $data['attributes'][$i][3] = $eavAttributeUuid;
                }
            }

            // $data['groups'] contains the following
            // eav_attribute_group table values
            //
            // [0] attribute_group_id or "ynode-NUM" for new
            // [1] attribute_group_name
            // [2] sort_order
            foreach ($data['groups'] as $i => $group) {
                $attributeGroupId = $group[0];

                if (!strncmp($attributeGroupId, 'ynode', strlen('ynode'))) {
                    continue;
                }

                $attributeGroup = Mage::getModel('eav/entity_attribute_group')->load($attributeGroupId);
                $attributeSetId = $attributeGroup->getAttributeSetId();
                $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);

                $attributeSetUuid = $attributeSet->getAttributeSetName();

                $attributeGroupUuid = $attributeGroup->getAttributeGroupName()
                        . self::UUID_SEPARATOR . $attributeSetUuid;

                $data['groups'][$i][0] = $attributeGroupUuid;
            }

            // $data['not_attributes'] contains the ID of attributes to be
            // unassociated
            foreach ($data['not_attributes'] as $i => $attributeId) {
                if (empty($attributeId)) {
                    continue;
                }
                $eavEntityAttributeRow = $helper->getEavEntityAttributeRow($attributeId);

                $attributeSetId = $eavEntityAttributeRow['attribute_set_id'];
                $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
                $attributeSetUuid = $attributeSet->getAttributeSetName();

                $attributeGroupId = $eavEntityAttributeRow['attribute_group_id'];
                $attributeGroup = Mage::getModel('eav/entity_attribute_group')->load($attributeGroupId);
                $attributeGroupUuid = $attributeGroup->getAttributeGroupName()
                        . self::UUID_SEPARATOR . $attributeSetUuid;

                $attributeUuid = $helper->getAttributeCodeFromId($eavEntityAttributeRow['attribute_id']);

                $eavEntityAttributeUuid = $attributeGroupUuid
                        . self::UUID_SEPARATOR . $attributeUuid;

                $data['not_attributes'][$i] = $eavEntityAttributeUuid;
            }

            // $data['removeGroups'] contains the ID of Attribute Group to be
            // deleted
            foreach ($data['removeGroups'] as $i => $groupId) {
                if (empty($groupId)) {
                    continue;
                }
                $group = Mage::getModel('eav/entity_attribute_group')->load($groupId);
                $attributeSetId = $group->getAttributeSetId();
                $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
                $attributeSetUuid = $attributeSet->getAttributeSetName();
                $attributeGroupUuid = $group->getAttributeGroupName() . self::UUID_SEPARATOR . $attributeSetUuid;
                $data['removeGroups'][$i] = $attributeGroupUuid;
            }

            // The json encoding is left to the decode() function
            $params['data'] = $data;

            $result[self::INDEX_EXECUTOR_CLASS] = get_class($this);
            $result[self::INDEX_CONTROLLER_MODULE] = $this->_request->getControllerModule();
            $result[self::INDEX_CONTROLLER_NAME] = $this->_request->getControllerName();
            $result[self::INDEX_ACTION_NAME] = $this->_request->getActionName();
            $result[self::INDEX_ACTION_PARAMS] = $this->_encodeParams($params);
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s Attribute Set with UUID '%s'", ucfirst($this->_request->getActionName()), $newOrExisting, $params['mageploy_uuid']);
            $result[self::INDEX_VERSION] = $this->_getVersion();
        } else {
            $result = false;
        }

        return $result;
    }

    /*
     * return Mage_Core_Controller_Request_Http
     */

    public function decode($encodedParameters, $version) {
        // The !empty() ensures that rows without a version number can be 
        // executed (not without any risk).
        if (!empty($version) && $this->_getVersion() != $version) {
            throw new Exception(sprintf("Can't decode the Action encoded with %s Tracker v %s; current Attribute Set Tracker is v %s ", $this->_code, $version, $this->_getVersion()));
        }

        $helper = Mage::helper('pugmore_mageploy');

        $parameters = $this->_decodeParams($encodedParameters);
        $attributeSetName = $parameters['mageploy_uuid'];
        $entityTypeId = $this->_getEntityTypeId();
        $entityTypeCode = $this->_getEntityTypeCode();

        if ($attributeSetId = $helper->getAttributeSetIdFromName($attributeSetName)) {
            $parameters['id'] = $attributeSetId;
        }

        $skeletonSetName = $parameters['skeleton_set'];
        if ($skeletonSetId = $helper->getAttributeSetIdFromName($skeletonSetName)) {
            $parameters['skeleton_set'] = $skeletonSetId;
        }

        $data = $parameters['data'];

        foreach ($data['attributes'] as $i => $attribute) {
            $attributeUuid = $attribute[0];
            $attributeId = $helper->getAttributeIdFromCode($attributeUuid, $entityTypeId);

            if ($attributeId == 208) {
                $break = $here;
            }
            
            $attributeGroupUuid = $attribute[1];

            // Is new Group?
            if (!strncmp($attributeGroupUuid, 'ynode', strlen('ynode'))) {
                $attributeGroupId = $attributeGroupUuid;
                $eavAttributeId = null;
            } else {
                // $attributeSetUuid is not used; we still have to explode the 
                // string in order to mantain compatibility with encoding
                list($attributeGroupName, $attributeSetUuid) = explode(self::UUID_SEPARATOR, $attributeGroupUuid);
                $attributeGroupId = $helper->getAttributeGroupId($attributeSetId, $attributeGroupName);
                $eavAttributeId = $helper->getEavEntityAttributeId($entityTypeId, $attributeSetId, $attributeGroupId, $attributeId);
            }

            $data['attributes'][$i][0] = $attributeId;
            $data['attributes'][$i][1] = $attributeGroupId;
            if ($eavAttributeId) {
                $data['attributes'][$i][3] = $eavAttributeId;
            } else {
                $data['attributes'][$i][3] = '';
            }
        }

        foreach ($data['groups'] as $i => $group) {
            $attributeGroupUuid = $group[0];
            if (!strncmp($attributeGroupUuid, 'ynode', strlen('ynode'))) {
                continue;
            }

            list($attributeGroupName, $attributeSetUuid) = explode(self::UUID_SEPARATOR, $attributeGroupUuid, 2);
            $attributeSetName = $attributeSetUuid;
            $attributeSetId = $helper->getAttributeSetId($attributeSetName, $entityTypeCode);
            $attributeGroupId = $helper->getAttributeGroupId($attributeSetId, $attributeGroupName);
            $data['groups'][$i][0] = $attributeGroupId;
        }

        foreach ($data['not_attributes'] as $i => $eavAttributeUuid) {
            if (empty($eavAttributeUuid)) {
                continue;
            }
            list($attributeGroupName, $attributeSetName, $attributeCode) = explode(self::UUID_SEPARATOR, $eavAttributeUuid);
            $attributeSetId = $helper->getAttributeSetId($attributeSetName, $entityTypeCode);
            $attributeGroupId = $helper->getAttributeGroupId($attributeSetId, $attributeGroupName);

            $attributeId = $helper->getAttributeIdFromCode($attributeCode, $entityTypeId);

            $entityAttributeId = $helper->getEavEntityAttributeId($entityTypeId, $attributeSetId, $attributeGroupId, $attributeId);
            $data['not_attributes'][$i] = $entityAttributeId;
        }

        foreach ($data['removeGroups'] as $i => $attributeGroupUuid) {
            if (empty($attributeGroupUuid)) {
                continue;
            }
            list ($attributeGroupName, $attributeSetName) = explode(self::UUID_SEPARATOR, $attributeGroupUuid);
            $attributeSetId = $helper->getAttributeSetId($attributeSetName, $entityTypeCode);
            $attributeGroupId = $helper->getAttributeGroupId($attributeSetId, $attributeGroupName);
            $data['removeGroups'][$i] = $attributeGroupId;
        }

        // json encode data array
        $parameters['data'] = Zend_Json::encode($data);

        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $request->setQuery($parameters);
        return $request;
    }

}
