<?php
/**
 * Description of Attribute
 *
 * @author AlessaPugMoRe_Mageploy_Model_Action_Abstractndro Ronchi <aronchi at webgriffe.com>
 */
abstract class PugMoRe_Mageploy_Model_Action_Abstract {
    const INDEX_ACTION_TIMESTAMP    = 'timestamp';
    const INDEX_ACTION_UUID         = 'uuid';
    const INDEX_ACTION_USER         = 'user';
    const INDEX_ACTION_DESCR        = 'description';
    const INDEX_EXECUTOR_CLASS      = 'executor_class';
    const INDEX_CONTROLLER_MODULE   = 'controller_module';
    const INDEX_CONTROLLER_NAME     = 'controller_name';
    const INDEX_ACTION_NAME         = 'action_name';
    const INDEX_ACTION_PARAMS       = 'action_params';
    const INDEX_VERSION             = 'version';
    
    const UUID_SEPARATOR = '~';
    
    protected $_code = '';
    
    /*
     * @var Mage_Core_Controller_Request_Http
     */
    protected $_request;
    
    protected abstract function _getVersion();
    
    protected function _encodeParams($params) {
        return json_encode($params);
    }
    
    protected function _decodeParams($params) {
        return json_decode($params, true);
    }
    
    public function toString() {
        return $this->_code;
    }
    
    public function setRequest($request) {
        $this->_request = $request;
        return $this;
    }
    
    public function match() {
        $h = Mage::helper('pugmore_mageploy');
        $h->log("Module name: %s", $this->_request->getModuleName());
        $h->log("Controller name: %s", $this->_request->getControllerName());
        $h->log("Action name: %s", $this->_request->getActionName());
        $h->log("Request Parameters: %s", print_r($this->_request->getParams(), true));
        return false;
    }

    public function encode() {
        // Insert Black Swan reference here
        $uuid = sha1(uniqid(Mage::helper('pugmore_mageploy')->getUser(), true));

        $result = array(
            // we use the micro time this as unique key. it is very unlikely that two persons do an action at the very same time
            self::INDEX_ACTION_UUID => $uuid,
            self::INDEX_ACTION_TIMESTAMP => Mage::getSingleton('core/date')->gmtDate('Y-m-d_H_i_s'),
            self::INDEX_ACTION_USER => Mage::helper('pugmore_mageploy')->getUser(),
        );
        return $result;
    }
    
    public function decode($encodedParameters, $version) {
        $request = new Mage_Core_Controller_Request_Http();
        return $request;
    }
}
