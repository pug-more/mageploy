<?php
/**
 * Description of Attribute
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Catalog_Product_Attribute extends PugMoRe_Mageploy_Model_Action_Abstract {

    protected $_code = 'catalog_product_attribute';
    
    protected $_blankableParams = array('attribute_id', 'key', 'form_key', 'back', 'tab');
    
    
   protected function _getAdminOptionValueByOptionId($attributeId, $optionId) {
        $option = Mage::getResourceModel('eav/entity_attribute_option_collection')
                                    ->setAttributeFilter($attributeId)
                                    ->addFieldToFilter('main_table.option_id', $optionId)
                                    ->setStoreFilter(0)
                                    ->getFirstItem();       
       return $option->getValue();
               
   }
    
    public function match() {
        if (!$this->_request) {
            return false;
        }
        
        if ($this->_request->getModuleName() == 'admin')
        {
            if ($this->_request->getControllerName() == 'catalog_product_attribute') {
                if (in_array($this->_request->getActionName(), array(/*'validate', */'save', 'delete'))) {
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
            
            if (isset($params['attribute_code'])) {
                // new entity
                // in case of options the only action we have to take is that of
                // converting Store IDs to Store UUIDs which is done below
                $isNew = true;
                #$params['mageploy_uuid'] = $params['attribute_code'];
            } else {
                // existing entity
                // in case of options the only thing we can do is considering 
                // all options as new; when decoding we will try to guess if
                // existing options have changed; there isn't any way of
                // uniquely idenfifying an option
                $attribute = Mage::getModel('catalog/entity_attribute')->load($params['attribute_id']);
                #$params['mageploy_uuid'] = $attribute->getAttributeCode();
                $params['attribute_id'] = $attribute->getAttributeCode();
                $isNew = false;
            }
            
            
            $newOption = $params['option'];
            
            // Attribute Options
            if (isset($params['option'])) {
                $option = $params['option'];
                foreach ($option['value'] as $optionId => $optionValues) {
                    foreach ($optionValues as $storeId => $value) {
                        if (!$storeId) {
                            if (is_numeric($optionId)) {
                                $optionUuid = 'existing_opt'.self::UUID_SEPARATOR.$value;
                            } else {
                                $optionUuid = 'new_opt'.self::UUID_SEPARATOR.$optionId;
                            }
                            #continue;
                        }
                        
                        $storeCode = Mage::getModel('core/store')->load($storeId)->getCode();    
                        unset($newOption['value'][$optionId][$storeId]);
                        $newOption['value'][$optionId][$storeCode] = $value;
                    }
                    $newOption['value'][$optionUuid] = $newOption['value'][$optionId];
                    unset($newOption['value'][$optionId]);
                }

                foreach ($option['order'] as $optionId => $order) {
                    $value = $this->_getAdminOptionValueByOptionId($params['attribute_id'], $optionId);
                    if (is_numeric($optionId)) {
                        $optionUuid = 'existing_opt'.self::UUID_SEPARATOR.$value;
                    } else {
                        $optionUuid = 'new_opt'.self::UUID_SEPARATOR.$optionId;
                    }
                    unset($newOption['order'][$optionId]);
                    $newOption['order'][$optionUuid] = $order;
                }

                foreach ($option['delete'] as $optionId => $delete) {
                    $value = $this->_getAdminOptionValueByOptionId($params['attribute_id'], $optionId);
                    if (is_numeric($optionId)) {
                        $optionUuid = 'existing_opt'.self::UUID_SEPARATOR.$value;
                    } else {
                        $optionUuid = 'new_opt'.self::UUID_SEPARATOR.$optionId;
                    }
                    unset($newOption['delete'][$optionId]);
                    $newOption['delete'][$optionUuid] = $delete;

                }
                $params['option'] = $newOption;
            }
            
            // Default Option
            if (isset($params['default'])) {
                $optionId = $params['default'][0];
                if (is_numeric($optionId)) {
                    $value = $this->_getAdminOptionValueByOptionId($params['attribute_id'], $optionId);
                    $params['default'][0] = 'existing_opt'.self::UUID_SEPARATOR.$value;
                } else {
                    $params['default'][0] = 'new_opt'.self::UUID_SEPARATOR.$optionId;
                }
                $params['default'][0] = 'option_'. $params['default'][0];
            }
            
            // Convert Frontend Label's Store Ids
            $newFrontendLabel = array();
            foreach ($params['frontend_label'] as $storeId => $value) {
                $code = Mage::getModel('core/store')->load($storeId)->getCode();
                $newFrontendLabel[$code] = $value;
            }
            $params['frontend_label'] = $newFrontendLabel;
            
            
            foreach ($this->_blankableParams as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                }
            }
            
            $result[self::INDEX_EXECUTOR_CLASS] = get_class($this);
            #$result[] = $this->_request->getModuleName();
            $result[self::INDEX_CONTROLLER_MODULE] = $this->_request->getControllerModule();
            $result[self::INDEX_CONTROLLER_NAME] = $this->_request->getControllerName();
            $result[self::INDEX_ACTION_NAME] = $this->_request->getActionName();
            $result[self::INDEX_ACTION_PARAMS] = serialize($params);
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s Attribute with UUID '%s'", ucfirst($this->_request->getActionName()), ($isNew ? 'new' : 'existing'), $params['attribute_id']);
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
        #$attributeCode = $parameters['mageploy_uuid'];
        if (is_set($parameters['attribute_code'])) {
            $attributeCode = $parameters['attribute_code'];
        } else {
            $attributeCode = $parameters['attribute_id'];
        }
        
        if ($attributeId = Mage::helper('pugmore_mageploy')->getAttributeIdFromCode($attributeCode)) {
            $parameters['attribute_id'] = $attributeId;
        }
        
        // TODO Decode Options
        
        // TODO Decode Default Option
        
        // TODO Decode Frontend Label
        
        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $request->setQuery($parameters);
        return $request;
    }
}
