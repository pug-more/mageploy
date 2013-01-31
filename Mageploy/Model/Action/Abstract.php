<?php
/**
 * Description of Attribute
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
abstract class PugMoRe_Mageploy_Model_Action_Abstract {
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
