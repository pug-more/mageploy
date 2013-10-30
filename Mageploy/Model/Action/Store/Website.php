<?php
/**
 * Description of Website
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Store_Website extends PugMoRe_Mageploy_Model_Action_Abstract
{
    const VERSION = '1'; // Change this only if encoding/decoding format changes
    
    protected $_code = 'system_store_website';
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
                if (in_array($this->_request->getActionName(), array('deleteWebsitePost'))) {
                    return true;
                }
                if (in_array($this->_request->getActionName(), array('save'))
                    && $this->_request->getParam('store_type') == 'website') {
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

            // Init log vars
            $new = 'new';
            $websiteCode = (isset($params['website']) ? $params['website']['code'] : '<undefined>');
            $actionName = $this->_request->getActionName();

            switch ($params['store_action']) {
                //
                // Handle saving of existing Website
                //
                case 'edit':
                    // Adapt log vars
                    $new = 'existing';
                    $website = Mage::getModel('core/website')->load($params['website']['website_id']);
                    if ($website->getId()) {
                        $websiteCode = $website->getCode();
                    }

                    // Convert Default Group ID
                    $defaultGroupId = $params['website']['default_group_id'];
                    $defaultGroup = Mage::getModel('core/store_group')->load($defaultGroupId);
                    if ($defaultGroup->getId()) {
                        $params['website']['default_group_id'] = $defaultGroup->getName();
                    }

                    // Convert Website ID
                    $params['website']['website_id'] = $websiteCode;

                    // break intentionally omitted

                //
                // Handle adding new Website
                //
                case 'add':
                    // Nothing to convert

                    break;

                //
                // Handle deleting existing Website
                // store_action parameter is undefined in case of delete
                //
                default:
                    // Adapt log vars and Convert Item ID
                    $new = 'existing';
                    $actionName = 'delete';
                    $website = Mage::getModel('core/website')->load($params['item_id']);
                    if ($website->getId()) {
                        $websiteCode = $params['item_id'] = $website->getCode();
                    }

                    break;
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
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s Website '%s'", ucfirst($actionName), $new, $websiteCode);
            $result[self::INDEX_VERSION] = $this->_getVersion();
        }
        
        return $result;
    }

    public function decode($encodedParameters, $version) {
        // The !empty() ensures that rows without a version number can be 
        // executed (not without any risk).
        if (!empty($version) && $this->_getVersion() != $version) {
            throw new Exception(sprintf("Can't decode the Action encoded with %s Tracker v %s; current Website Tracker is v %s ", $this->_code, $version, $this->_getVersion()));
        }

        $params = $this->_decodeParams($encodedParameters);

        switch ($params['store_action']) {
            //
            // Handle saving of existing Website
            //
            case 'edit':
                // Convert Default Group UUID
                $defaultGroupUuid = $params['website']['default_group_id'];
                $defaultGroup = Mage::getModel('core/store_group')->load($defaultGroupUuid, 'name');
                if ($defaultGroup->getId()) {
                    $params['website']['default_group_id'] = $defaultGroup->getId();
                } else {
                    throw new Exception('Group \''.$defaultGroupUuid.'\' not found!');
                }

                // Convert Website UUID
                $websiteUuid = $params['website']['website_id'];
                $website = Mage::getModel('core/website')->load($websiteUuid, 'code');
                if ($website->getId()) {
                    $params['website']['website_id'] = $website->getId();
                } else {
                    throw new Exception('Website \''.$websiteUuid.'\' not found!');
                }

                // break intentionally omitted

            //
            // Handle adding new Website
            //
            case 'add':
                // Nothing to convert

                break;

            //
            // Handle deleting existing Website
            // store_action parameter is undefined in case of delete
            //
            default:
                //Convert Item UUID
                $itemUuid = $params['item_id'];
                $website = Mage::getModel('core/website')->load($itemUuid, 'code');
                if ($website->getId()) {
                    $params['item_id'] = $website->getId();
                } else {
                    throw new Exception('Website \''.$itemUuid.'\' not found!');
                }
                break;
        }

        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($params);
        $_SERVER['REQUEST_METHOD'] = 'POST'; // needed by StoreController
        $request->setQuery($params);
        return $request;
    }
    
}
