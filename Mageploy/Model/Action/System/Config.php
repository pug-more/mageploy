<?php

/**
 * Description of SystemConfig
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_System_Config extends PugMoRe_Mageploy_Model_Action_Abstract {

    const VERSION = '1';

    protected $_code = 'system_config';
    protected $_blankableParams = array('key', 'form_key');

    protected function _getVersion() {
        return Mage::helper('pugmore_mageploy')->getVersion(2).'.'.self::VERSION;
    }
    
    public function match() {
        if (!$this->_request) {
            return false;
        }

        if ($this->_request->getModuleName() == 'admin') {
            if ($this->_request->getControllerName() == 'system_config') {
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

            foreach ($this->_blankableParams as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                }
            }
            
            // Prevent propagating changes on mageploy's configuration
            if (isset($params['groups']['mageploy']/*['fields']['user']*/)) {
                unset($params['groups']['mageploy']/*['fields']['user']*/);
            }
            
            $result[self::INDEX_EXECUTOR_CLASS] = get_class($this);
            #$result[self::INDEX_CONTROLLER_MODULE] = $this->_request->getControllerModule();
            $result[self::INDEX_CONTROLLER_MODULE] = 'PugMoRe_Mageploy';
            $result[self::INDEX_CONTROLLER_NAME] = $this->_request->getControllerName();
            $result[self::INDEX_ACTION_NAME] = $this->_request->getActionName();
            $result[self::INDEX_ACTION_PARAMS] = $this->_encodeParams($params);
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s System Config", ucfirst($this->_request->getActionName()));
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
            throw new Exception(sprintf("Can't decode the Action encoded with %s Tracker v %s; current Block Tracker is v %s ", $this->_code, $version, $this->_getVersion()));
        }

        $parameters = $this->_decodeParams($encodedParameters);
        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $request->setQuery($parameters);
        // @todo $_FILE?
        return $request;
    }

}