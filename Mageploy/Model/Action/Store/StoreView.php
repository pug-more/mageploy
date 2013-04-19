<?php
/**
 * Description of StoreView
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Store_StoreView extends PugMoRe_Mageploy_Model_Action_Abstract {
    const VERSION = '1';
    
    protected $_code = 'system_store';
    protected $_blankableParams = array('key', 'form_key');

    protected function _getVersion() {
        return Mage::helper('pugmore_mageploy')->getVersion(2).'.'.self::VERSION;
    }
    
    public function match() {
        if (!$this->_request) {
            return false;
        }

        if ($this->_request->getModuleName() == 'admin') {
            if ($this->_request->getControllerName() == 'system_store') {
                if (in_array($this->_request->getActionName(), array('deleteStorePost'))) {
                    return true;
                }
                if (in_array($this->_request->getActionName(), array('save'))
                    && $this->_request->getParam('store_type') == 'store') {
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
        
            // convert Group ID
            if (isset($params['store']) && ($groupId = $params['store']['group_id'])) {
                $group = Mage::getModel('core/store_group')->load($groupId);
                if ($group->getId()) {
                    $params['store']['group_id'] = $group->getName();
                }
            }

            $new = 'new';
            $actionName = $this->_request->getActionName();
            if (isset($params['store'])) {
                $storeCode = $params['store']['code'];            
            }
            
            // Convert Store ID
            if (isset($params['store']) && $storeId = $params['store']['store_id']) {
                $new = 'existing';
                $params['store']['store_id'] = $storeCode;
            }
            
            // Convert Item ID (for deleteStore action)
            if ($itemId = $params['item_id']) {
                $new = 'existing';
                $store = Mage::getModel('core/store')->load($itemId);
                if ($store->getId()) {
                    $storeCode = $params['item_id'] = $store->getCode();
                    $actionName = 'delete';
                }
            }
            
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
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s Store View '%s'", ucfirst($actionName), $new, $storeCode);
            $result[self::INDEX_VERSION] = $this->_getVersion();
        }
        
        
        return $result;
    }

    public function decode($encodedParameters, $version) {
        // The !empty() ensures that rows without a version number can be 
        // executed (not without any risk).
        if (!empty($version) && $this->_getVersion() != $version) {
            throw new Exception(sprintf("Can't decode the Action encoded with %s Tracker v %s; current Store View Tracker is v %s ", $this->_code, $version, $this->_getVersion()));
        }

        $parameters = $this->_decodeParams($encodedParameters);
        
        // Convert Group UUID
        if (isset($parameters['store']) && ($groupUuid = $parameters['store']['group_id'])) {
            $group = Mage::getModel('core/store_group')->load($groupUuid, 'name');
            if ($group->getId()) {
                $parameters['store']['group_id'] = $group->getId();
            }
        }
        
        // Convert Store UUID
        if (isset($parameters['store']) && $storeUuid = $parameters['store']['store_id']) {
            $store = Mage::getModel('core/store')->load($storeUuid, 'code');
            $parameters['store']['store_id'] = $store->getId();
        }        
        
        // Convert Item UUID (for deleteStore action)
        if ($itemUuid = $parameters['item_id']) {
            $store = Mage::getModel('core/store')->load($itemUuid, 'code');
            if ($store->getId()) {
                $parameters['item_id'] = $store->getId();
            }
        }
        
        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $_SERVER['REQUEST_METHOD'] = 'POST'; // checked by StoreController
        $request->setQuery($parameters);
        return $request;
    }
    
}
