<?php
/**
 * Description of Attribute
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Catalog_Product_Attribute extends PugMoRe_Mageploy_Model_Action_Abstract {

    protected $_code = 'catalog_product_attribute';
    
    protected $_blankableParams = array('attribute_id', 'key', 'form_key', 'back', 'tab');
    
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
        $result = array();
        if ($this->_request) {
            $params = $this->_request->getParams();
            
            $newOrExisting = '';
            if (isset($params['attribute_code'])) {
                // new entity
                $params['mageploy_uuid'] = $params['attribute_code'];
                $newOrExisting = 'new';
            } else {
                // existing entity
                $attribute = Mage::getModel('catalog/entity_attribute')->load($params['attribute_id']);
                $params['mageploy_uuid'] = $attribute->getAttributeCode();
                $newOrExisting = 'existing';
            }
            
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
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s Attribute with UUID '%s'", ucfirst($this->_request->getActionName()), $newOrExisting, $params['mageploy_uuid']);
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
        $attributeCode = $parameters['mageploy_uuid'];
        
        if ($attributeId = Mage::helper('pugmore_mageploy')->getAttributeIdFromCode($attributeCode)) {
            $parameters['attribute_id'] = $attributeId;
        }
        
        $request = new Mage_Core_Controller_Request_Http();
        #$request->setParams($parameters);
        $request->setPost($parameters);
        $request->setQuery($parameters);
        return $request;
    }
}
