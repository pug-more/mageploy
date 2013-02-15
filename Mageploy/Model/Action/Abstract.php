<?php
/**
 * Description of Attribute
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
abstract class PugMoRe_Mageploy_Model_Action_Abstract {
    const INDEX_ACTION_TIMESTAMP    = 0;
    const INDEX_ACTION_USER         = 1;
    const INDEX_ACTION_DESCR        = 2;
    const INDEX_EXECUTOR_CLASS      = 3;
    const INDEX_CONTROLLER_MODULE   = 4;
    const INDEX_CONTROLLER_NAME     = 5;
    const INDEX_ACTION_NAME         = 6;
    const INDEX_ACTION_PARAMS       = 7;
    
    protected $_code = '';
    
    /*
     * @var Mage_Core_Controller_Request_Http
     */
    protected $_request;
    
    public function toString() {
        return $this->_code;
    }
    
    public function setRequest($request) {
        $this->_request = $request;
        return $this;
    }
    
    public function match() {
        return false;
    }

    public function encode() {
        $result = array(
            self::INDEX_ACTION_TIMESTAMP => time(),
            self::INDEX_ACTION_USER => Mage::helper('pugmore_mageploy')->getUser(),
        );
        return $result;
    }
    
    public function decode($serializedParameters) {
        
    }
}
