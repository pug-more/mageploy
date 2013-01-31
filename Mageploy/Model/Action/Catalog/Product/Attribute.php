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
            
            if (isset($params['attribute_code'])) {
                // new entity
                $params['mageploy_uuid'] = $params['attribute_code'];
            } else {
                // existing entity
                $attribute = Mage::getModel('catalog/entity_attribute')->load($params['attribute_id']);
                $params['mageploy_uuid'] = $attribute->getAttributeCode();
            }
            
            foreach ($this->_blankableParams as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                }
            }
            
            $result[] = get_class($this);
            $result[] = $this->_request->getModuleName();
            $result[] = $this->_request->getControllerName();
            $result[] = $this->_request->getActionName();
            $result[] = serialize($params);
        } else {
            $result = false;
        }
        return $result;
    }
    
    /*
     * return Mage_Core_Controller_Request_Http
    public function decode($stream) {
        $result = array();
        if ($this->_request) {
            
        } else {
            $result = false;
        }
        return $result;
    }
     */ 

}
