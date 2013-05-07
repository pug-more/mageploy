<?php

/**
 * Description of SystemConfig
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Cms_Page extends PugMoRe_Mageploy_Model_Action_Abstract {

    const VERSION = '1';

    protected $_code = 'cms_page';
    protected $_blankableParams = array('key', 'form_key');

    protected function _getVersion() {
        return Mage::helper('pugmore_mageploy')->getVersion(2).'.'.self::VERSION;
    }
    
    public function match() {
        if (!$this->_request) {
            return false;
        }

        if ($this->_request->getModuleName() == 'admin') {
            if ($this->_request->getControllerName() == 'cms_page') {
                if (in_array($this->_request->getActionName(), array('save', 'delete'))) {
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
            
            // convert store IDs
            foreach ($params['stores'] as $i => $storeId) {
                $storeUuid = Mage::app()->getStore($storeId)->getCode();
                $params['stores'][$i] = $storeUuid;
            }
            
            // convert ID, if page already exists
            $new = 'new';
            if (isset($params['page_id'])) {
                $new = 'existing';
                $params['page_id'] = $params['identifier'] . self::UUID_SEPARATOR 
                        . implode(self::UUID_SEPARATOR, $params['stores']);
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
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s CMS Page '%s'", ucfirst($this->_request->getActionName()), $new, $params['identifier']);
            $result[self::INDEX_VERSION] = $this->_getVersion();
        } else {
            $result = false;
        }
        return $result;
    }

    public function decode($encodedParameters, $version) {
        // The !empty() ensures that rows without a version number can be 
        // executed (not without any risk).
        if (!empty($version) && $this->_getVersion() != $version) {
            throw new Exception(sprintf("Can't decode the Action encoded with %s Tracker v %s; current CMS Page Tracker is v %s ", $this->_code, $version, $this->_getVersion()));
        }

        $parameters = $this->_decodeParams($encodedParameters);
        
        // convert store IDs
        foreach ($parameters['stores'] as $i => $storeUuid) {
            $storeId = Mage::app()->getStore($storeUuid)->getId();
            $parameters['stores'][$i] = $storeId;
        }
        
        // convert UUID, if page already exists
        if (isset($parameters['page_id'])) {
            list($identifier, $joinedStoreCodes) = explode(self::UUID_SEPARATOR, $parameters['page_id'], 2);
            $storeCodes = explode(self::UUID_SEPARATOR, $joinedStoreCodes);
            $storeId = Mage::app()->getStore($storeCodes[0])->getId();
            
            $page = Mage::getModel('cms/page')->getCollection()
                    ->addStoreFilter($storeId, false)
                    ->addFieldToFilter('identifier', $identifier)
                    ->getFirstItem();

            /**
             * On pages created out of the box by Magento, the addStoreFilter
             * doesn't seem to return the requested object.
             */
            if (!$page->getId()) {
                $page = Mage::getModel('cms/page')->getCollection()
                    ->addFieldToFilter('identifier', $identifier)
                    ->getFirstItem();
            }

            $parameters['page_id'] = $page->getId();
        }
        
        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $request->setQuery($parameters);
        return $request;
    }

}