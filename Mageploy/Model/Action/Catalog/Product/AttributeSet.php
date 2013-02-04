<?php
/**
 * Description of Attribute Set
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Catalog_Product_AttributeSet extends PugMoRe_Mageploy_Model_Action_Abstract {

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
                #$attribute = Mage::getModel('catalog/entity_attribute')->load($params['attribute_id']);
                $params['mageploy_uuid'] = $attributeSet->getAttributeSetName();
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
        
        $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($attributeSetName, 'attribute_set_name');
        
        if ($attributeSetId = $attributeSet->getId()) {
            $parameters['id'] = $attributeSetId;
        }
        $request = new Mage_Core_Controller_Request_Http();
        #$request->setParams($parameters);
        $request->setPost($parameters);
        $request->setQuery($parameters);
        return $request;
    }
}
