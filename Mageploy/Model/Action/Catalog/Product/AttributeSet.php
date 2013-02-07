<?php
/**
 * Description of Attribute Set
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Catalog_Product_AttributeSet extends PugMoRe_Mageploy_Model_Action_Abstract {
    
    const UUID_SEPARATOR = '~';

    protected $_code = 'catalog_product_attributeSet';
    
    protected $_blankableParams = array('id', 'key', 'isAjax', 'gotoEdit', 'form_key');
    
    public function match() {
        if (!$this->_request) {
            return false;
        }
        
        if ($this->_request->getModuleName() == 'admin')
        {
            if ($this->_request->getControllerName() == 'catalog_product_set') {
                if (in_array($this->_request->getActionName(), array(/*'validate', */'save', 'delete'))) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function encode() {
        $result = array();

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
            
            foreach ($this->_blankableParams as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                }
            }
            
            // json decode $params['data']
            $data = Mage::helper('core')->jsonDecode($params['data']);
            if (isset($data['form_key'])) {
                unset($params['form_key']);
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
                $attributeSetUuid = $attributeSet->getAttributeSetName()
                        . self::UUID_SEPARATOR . $helper->getEntityTypeCodeFromId($attributeSet->getEntityTypeId());
                
                $attributeGroup = Mage::getModel('eav/entity_attribute_group')->load($attributeGroupId);
                $attributeGroupUuid = $attributeGroup->getAttributeGroupName()
                        . self::UUID_SEPARATOR . $attributeSetUuid;
                
                $eavAttributeUuid = $helper->getEntityTypeCodeFromId($attributeSet->getEntityTypeId())
                        . self::UUID_SEPARATOR . $attributeSetUuid 
                        . self::UUID_SEPARATOR . $attributeGroupUuid 
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
                
                $attributeGroup = Mage::getModel('eav/entity_attribute_group')->load($attributeGroupId);
                $attributeSetId = $attributeGroup->getAttributeSetId();
                $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
                
                $attributeSetUuid = $attributeSet->getAttributeSetName()
                        . self::UUID_SEPARATOR . $helper->getEntityTypeCodeFromId($attributeSet->getEntityTypeId());
                
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
                
                $entityTypeCode = $helper->getEntityTypeCodeFromId($eavEntityAttributeRow['entity_type_id']);
                
                $attributeSetId = $eavEntityAttributeRow['attribute_set_id'];
                $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetId);
                $attributeSetUuid = $attributeSet->getAttributeSetName()
                        . self::UUID_SEPARATOR . $helper->getEntityTypeCodeFromId($attributeSet->getEntityTypeId());
                
                $attributeGroupId = $eavEntityAttributeRow['attribute_group_id'];
                $attributeGroup = Mage::getModel('eav/entity_attribute_group')->load($attributeGroupId);
                $attributeGroupUuid = $attributeGroup->getAttributeGroupName()
                        . self::UUID_SEPARATOR . $attributeSetUuid;
                
                $attributeUuid = $helper->getAttributeCodeFromId($eavEntityAttributeRow['attribute_id']);
                
                $eavEntityAttributeUuid = $entityTypeCode
                    . self::UUID_SEPARATOR . $attributeSetUuid
                    . self::UUID_SEPARATOR . $attributeGroupUuid    
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
                $attributeSetUuid = $attributeSet->getAttributeSetName()
                        . self::UUID_SEPARATOR . $helper->getEntityTypeCodeFromId($attributeSet->getEntityTypeId());
                $attributeGroupUuid = $attributeSetUuid . self::UUID_SEPARATOR . $group->getAttributeGroupName();
                $data['removeGroups'][$i] = $attributeGroupUuid;
            }
            
            // @todo The json encoding is left to the decode() function 
            // $params['data'] = Mage::helper('core')->jsonEncode($data);
            $params['data'] = $data;
            
            $result[self::INDEX_EXECUTOR_CLASS] = get_class($this);
            $result[self::INDEX_CONTROLLER_MODULE] = $this->_request->getControllerModule();
            $result[self::INDEX_CONTROLLER_NAME] = $this->_request->getControllerName();
            $result[self::INDEX_ACTION_NAME] = $this->_request->getActionName();
            $result[self::INDEX_ACTION_PARAMS] = serialize($params);
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s Attribute Set with UUID '%s'", ucfirst($this->_request->getActionName()), $newOrExisting, $params['mageploy_uuid']);
        } else {
            $result = false;
        }

        return $result;
    }
    
    /*
     * return Mage_Core_Controller_Request_Http
     */
    public function decode($serializedParameters) {
        $parameters = unserialize($serializedParameters);
        $attributeSetName = $parameters['mageploy_uuid'];
        
        if ($attributeSetId = Mage::helper('pugmore_mageploy')->getAttributeSetIdFromName($attributeSetName)) {
            $parameters['id'] = $attributeSetId;
        }
        
        // @todo json_decode $params['data']
        // @todo convert attribute UUIDs to IDs in $params['data']
        // @todo json_encode $params['data']
        
        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $request->setQuery($parameters);
        return $request;
    }
}
