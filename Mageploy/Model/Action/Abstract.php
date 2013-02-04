<?php
/**
 * Description of Attribute
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
abstract class PugMoRe_Mageploy_Model_Action_Abstract {
    const INDEX_EXECUTOR_CLASS      = 0;
    const INDEX_CONTROLLER_MODULE   = 1;
    const INDEX_CONTROLLER_NAME     = 2;
    const INDEX_ACTION_NAME         = 3;
    const INDEX_ACTION_PARAMS       = 4;
    const INDEX_ACTION_DESCR        = 5;
    
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
    
    public abstract function match();
}
