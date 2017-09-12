<?php
/**
 * Description of StoreView
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Store_StoreView extends PugMoRe_Mageploy_Model_Action_Abstract
{
    const VERSION = '2'; // Change this only if encoding/decoding format changes

    protected $_code = 'system_store_storeview';
    protected $_blankableParams = array('key', 'form_key');

    protected function _getVersion() {
        return Mage::helper('pugmore_mageploy')->getVersion(2).'.'.self::VERSION;
    }

    public function match() {

        if (!$this->_request) {
            return false;
        }

        if ($this->isAdminRequest()) {
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

            // Init log vars
            $new = 'new';
            $storeCode = (isset($params['store']) ? $params['store']['code'] : '<undefined>');
            $actionName = $this->_request->getActionName();

            switch ($params['store_action']) {
                //
                // Handle saving of existing Store View
                //
                case 'edit':
                    // Adapt log vars
                    $new = 'existing';
                    $store = Mage::getModel('core/store')->load($params['store']['store_id']);
                    if ($store->getId()) {
                        $storeCode = $store->getCode();
                    }

                    // Convert Original Group ID
                    $originalGroupId = $params['store']['original_group_id'];
                    $originalGroup = Mage::getModel('core/store_group')->load($originalGroupId);
                    if ($originalGroup->getId()) {
                        $params['store']['original_group_id'] = $originalGroup->getName();
                    }

                    // Convert Store ID
                    $params['store']['store_id'] = $storeCode;

                    // break intentionally omitted

                //
                // Handle adding new Store View
                //
                case 'add':
                    // Covert Group ID
                    $groupId = $params['store']['group_id'];
                    $group = Mage::getModel('core/store_group')->load($groupId);
                    if ($group->getId()) {
                        $params['store']['group_id'] = $group->getName();
                    }

                    break;

                //
                // Handle deleting existing Store View
                // store_action parameter is undefined in case of delete
                //
                default:
                    // Adapt log vars and Convert Item ID
                    $new = 'existing';
                    $actionName = 'delete';
                    $store = Mage::getModel('core/store')->load($params['item_id']);
                    if ($store->getId()) {
                        $storeCode = $params['item_id'] = $store->getCode();
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

        $params = $this->_decodeParams($encodedParameters);

        switch ($params['store_action']) {
            //
            // Handle saving of existing Store View
            //
            case 'edit':
                // Convert Original Group UUID
                $originalGroupUuid = $params['store']['original_group_id'];
                $originalGroup = Mage::getModel('core/store_group')->load($originalGroupUuid, 'name');
                if ($originalGroup->getId()) {
                    $params['store']['original_group_id'] = $originalGroup->getId();
                } else {
                    throw new Exception('Group \''.$originalGroupUuid.'\' not found!');
                }

                // Convert Store UUID
                $storeUuid = $params['store']['store_id'];
                $store = Mage::getModel('core/store')->load($storeUuid, 'code');
                if ($store->getId()) {
                    $params['store']['store_id'] = $store->getId();
                } else {
                    throw new Exception('Store \''.$storeUuid.'\' not found!');
                }

                // break intentionally omitted

            //
            // Handle adding new Store View
            //
            case 'add':
                // Covert Group UUID
                $groupUuid = $params['store']['group_id'];
                $group = Mage::getModel('core/store_group')->load($groupUuid, 'name');
                if ($group->getId()) {
                    $params['store']['group_id'] = $group->getId();
                } else {
                    throw new Exception('Group \''.$groupUuid.'\' not found!');
                }

                break;

            //
            // Handle deleting existing Store View
            // store_action parameter is undefined in case of delete
            //
            default:
                //Convert Item UUID
                $itemUuid = $params['item_id'];
                $store = Mage::getModel('core/store')->load($itemUuid, 'code');
                if ($store->getId()) {
                    $params['item_id'] = $store->getId();
                } else {
                    throw new Exception('Store \''.$itemUuid.'\' not found!');
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
