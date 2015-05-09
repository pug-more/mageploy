<?php

/**
 * Description of Attribute
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Catalog_Product_Attribute extends PugMoRe_Mageploy_Model_Action_Abstract {

    const VERSION = '1';
    
    protected $_code = 'catalog_product_attribute';
    protected $_blankableParams = array('key', 'form_key', 'back', 'tab');

    protected function _getVersion() {
        return Mage::helper('pugmore_mageploy')->getVersion(2).'.'.self::VERSION;
    }
    
    protected function _getOptionIdByUuid($optionUuid, &$attributeOptionsByValue, &$newOptions)
    {
        $id = 0;
        list($newOrExisting, $adminValue) = explode(self::UUID_SEPARATOR, $optionUuid, 2);
        if (!strcmp('existing_opt', $newOrExisting)) {
            if (isset($attributeOptionsByValue[$adminValue])) {
                $id = $attributeOptionsByValue[$adminValue]['id'];
            }
        }
        if (!$id) {
            $id = 'option_' . count($newOptions);
            $attributeOptionsByValue[$adminValue]['id'] = $id; // so next call will return this id
            $attributeOptionsByValue[$adminValue]['order'] = 0;
            $newOptions[] = $id; // so we keep track of the number of new options created
        }
        return $id;
    }
    
    protected function _getOptionOrderByUuid($optionUuid, $attributeOptionsByValue)
    {
        $order = 0;
        list($newOrExisting, $adminValue) = explode(self::UUID_SEPARATOR, $optionUuid, 2);
        if (!strcmp('existing_opt', $newOrExisting)) {
            if (isset($attributeOptionsByValue[$adminValue])) {
                $order = $attributeOptionsByValue[$adminValue]['order'];
            }
        }
        return $order;
    }
    
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

        if ($this->_request->getModuleName() == 'admin') {
            if ($this->_request->getControllerName() == 'catalog_product_attribute') {
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

            if (isset($params['attribute_code'])) {
                // new entity
                // in case of options the only action we have to take is that of
                // converting Store IDs to Store UUIDs which is done below
                $isNew = true;
                #$params['mageploy_uuid'] = $params['attribute_code'];
                $attributeUuid = $params['attribute_code'];
            } else {
                // existing entity
                // in case of options the only thing we can do is considering 
                // all options as new; when decoding we will try to guess if
                // existing options have changed; there isn't any way of
                // uniquely idenfifying an option
                $attributeId = $params['attribute_id'];
                $attribute = Mage::getModel('catalog/entity_attribute')->load($attributeId);
                #$params['mageploy_uuid'] = $attribute->getAttributeCode();
                $attributeUuid = $attribute->getAttributeCode();
                $params['attribute_id'] = $attributeUuid;
                $isNew = false;
            }


            // Attribute Options
            if (isset($params['option'])) {

                $newOption = $params['option'];
                $option = $params['option'];

                foreach ($option['value'] as $optionId => $optionValues) {
                    foreach ($optionValues as $storeId => $value) {
                        if (!$storeId) {
                            if (is_numeric($optionId)) {
                                $optionUuid = 'existing_opt' . self::UUID_SEPARATOR . $value;
                            } else {
                                $optionUuid = 'new_opt' . self::UUID_SEPARATOR . $optionId;
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
                    $value = $this->_getAdminOptionValueByOptionId($attributeId, $optionId);
                    if (is_numeric($optionId)) {
                        $optionUuid = 'existing_opt' . self::UUID_SEPARATOR . $value;
                    } else {
                        $optionUuid = 'new_opt' . self::UUID_SEPARATOR . $optionId;
                    }
                    unset($newOption['order'][$optionId]);
                    $newOption['order'][$optionUuid] = $order;
                }

                foreach ($option['delete'] as $optionId => $delete) {
                    $value = $this->_getAdminOptionValueByOptionId($attributeId, $optionId);
                    if (is_numeric($optionId)) {
                        $optionUuid = 'existing_opt' . self::UUID_SEPARATOR . $value;
                    } else {
                        $optionUuid = 'new_opt' . self::UUID_SEPARATOR . $optionId;
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
                    $value = $this->_getAdminOptionValueByOptionId($attributeId, $optionId);
                    $params['default'][0] = 'existing_opt' . self::UUID_SEPARATOR . $value;
                } else {
                    $params['default'][0] = 'new_opt' . self::UUID_SEPARATOR . $optionId;
                }
                #$params['default'][0] = 'option_' . $params['default'][0]; // I don't remember what was this intended for, so I comment it :-(
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
            $result[self::INDEX_CONTROLLER_MODULE] = $this->_request->getControllerModule();
            $result[self::INDEX_CONTROLLER_NAME] = $this->_request->getControllerName();
            $result[self::INDEX_ACTION_NAME] = $this->_request->getActionName();
            $result[self::INDEX_ACTION_PARAMS] = $this->_encodeParams($params);
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s Attribute with UUID '%s'", ucfirst($this->_request->getActionName()), ($isNew ? 'new' : 'existing'), $attributeUuid);
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
            throw new Exception(sprintf("Can't decode the Action encoded with %s Tracker v %s; current Attribute Tracker is v %s ", $this->_code, $version, $this->_getVersion()));
        }

        $parameters = $this->_decodeParams($encodedParameters);
        #$attributeCode = $parameters['mageploy_uuid'];
        if (isset($parameters['attribute_code'])) {
            $attributeCode = $parameters['attribute_code'];
        } else {
            $attributeCode = $parameters['attribute_id'];
        }
        
        // Prepare the Attribute Option Structure that will be used to 
        // decode parameters and try to guess if an existing option has been 
        // changed or added.
        // Note: we are assuming we never change admin's value
        $attribute = Mage::getResourceModel('eav/entity_attribute_collection')
                    ->setCodeFilter($attributeCode)
                    ->getFirstItem();
        
        if ($attribute->getSourceModel()) {
            $attributeOptions = $attribute->getSource()->getAllOptions(false);
            $attributeOptionsByValue = array();
            foreach ($attributeOptions as $order => $valuelabel) {
                $optionId = $valuelabel['value'];
                $adminVal = $valuelabel['label'];
                $attributeOptionsByValue[$adminVal] = array('order' => $order, 'id' => $optionId);
            }
        }

        $entityTypeId = Mage::helper('pugmore_mageploy')->getEntityTypeIdFromCode(Mage_Catalog_Model_Product::ENTITY);
        if ($attributeId = Mage::helper('pugmore_mageploy')->getAttributeIdFromCode($attributeCode, $entityTypeId)) {
            $parameters['attribute_id'] = $attributeId;
        }

        // Decode Attribute Options
        if (isset($parameters['option'])) {
            $option = $parameters['option'];
            
            // Used to store new options
            $newOptions = array();
            
            // Value
            $newValues = array();
            foreach ($option['value'] as $optionUuid => $optionValues) {
                $optionId = $this->_getOptionIdByUuid($optionUuid, $attributeOptionsByValue, $newOptions);
                $newValues[$optionId] = array();
                foreach ($optionValues as $storeCode => $value) {
                    $storeId = Mage::getModel('core/store')->load($storeCode)->getId();
                    $newValues[$optionId][$storeId] = $value;
                }
            }
            $parameters['option']['value'] = $newValues;
            
            // Order
            $newOrders = array();
            foreach ($option['order'] as $optionUuid => $optionOrder) {
                $optionId = $this->_getOptionIdByUuid($optionUuid, $attributeOptionsByValue, $newOptions);
                $newOrders[$optionId] = $optionOrder;
            }
            $parameters['option']['order'] = $newOrders;
             
            // Delete
            $newDeletes = array();
            foreach ($option['delete'] as $optionUuid => $optionDelete) {
                $optionId = $this->_getOptionIdByUuid($optionUuid, $attributeOptionsByValue, $newOptions);
                $newDeletes[$optionId] = $optionDelete;
            }
            $parameters['option']['delete'] = $newDeletes;
            
        }

        // Decode Default Option
        if (isset($attributeOptionsByValue)) {
            $optionUuid = $parameters['default'][0];
            $parameters['default'][0] = $this->_getOptionIdByUuid($optionUuid, $attributeOptionsByValue, $newOptions);
        }
        
        // Decode Frontend Label
        $newFrontendLabel = array();
        foreach ($parameters['frontend_label'] as $storeCode => $label) {
            $storeId = Mage::getModel('core/store')->load($storeCode)->getId();
            $newFrontendLabel[$storeId] = $label;
        }
        $parameters['frontend_label'] = $newFrontendLabel;

        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $request->setQuery($parameters);
        return $request;
    }

}
