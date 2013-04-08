<?php

/**
 * Description of SystemConfig
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Cms_Block extends PugMoRe_Mageploy_Model_Action_Abstract {

    protected $_code = 'cms_block';
    protected $_blankableParams = array('key', 'form_key');

    public function match() {
        if (!$this->_request) {
            return false;
        }

        if ($this->_request->getModuleName() == 'admin') {
            if ($this->_request->getControllerName() == 'cms_block') {
                if (in_array($this->_request->getActionName(), array('save'))) {
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
            
            // convert block_id
            // for now assuming no two blocks using the same identifier
            if (isset($params['block_id'])) {
                $params['block_id'] = $params['identifier'];
            }
            
            // convert store ids
            foreach ($params['stores'] as $i => $storeId) {
                $storeUuid = Mage::app()->getStore($storeId)->getCode();
                $params['stores'][$i] = $storeUuid;
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
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s Static Block '%s'", ucfirst($this->_request->getActionName()), $params['identifier']);
        } else {
            $result = false;
        }
        return $result;
    }

    public function decode($encodedParameters) {
        $parameters = $this->_decodeParams($encodedParameters);
        
        // convert block_id
        // for now assuming no two blocks using the same identifier
        if (isset($parameters['block_id'])) {
            $block = Mage::getModel('cms/block')->load($parameters['block_id'], 'identifier');
            if ($block->getId()) {
                $parameters['block_id'] = $block->getId();
            }
        }
        
        // convert store ids
        foreach ($parameters['stores'] as $i => $storeUuid) {
            $storeId = Mage::app()->getStore($storeUuid)->getId();
            $parameters['stores'][$i] = $storeId;
        }        
        
        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $request->setQuery($parameters);
        return $request;
    }

}