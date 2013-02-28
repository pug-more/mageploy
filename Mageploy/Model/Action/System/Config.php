<?php

/**
 * Description of SystemConfig
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_System_Config extends PugMoRe_Mageploy_Model_Action_Abstract {

    protected $_code = 'system_config';
    protected $_blankableParams = array('key', 'form_key');

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
        } else {
            $result = false;
        }
        return $result;
    }

    public function decode($encodedParameters) {
        $parameters = $this->_decodeParams($encodedParameters);
        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($parameters);
        $request->setQuery($parameters);
        // @todo $_FILE?
        return $request;
    }

}