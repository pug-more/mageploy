<?php
/**
 * Store Group Tracker
 *
 * We assume that two different store groups associated to the same website
 * can't have the same name.
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Store_Group extends PugMoRe_Mageploy_Model_Action_Abstract
{
    const VERSION = '2'; // Change this only if encoding/decoding format changes

    protected $_code = 'system_store_group';
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
                if (in_array($this->_request->getActionName(), array('deleteGroupPost'))) {
                    return true;
                }
                if (in_array($this->_request->getActionName(), array('save'))
                    && $this->_request->getParam('store_type') == 'group') {
                        return true;
                }
            }
        }

        return false;
    }

    public function encode() {
        $result = parent::encode();
        $helper = Mage::helper('pugmore_mageploy');

        if ($this->_request) {
            $params = $this->_request->getParams();

            // Init log vars
            $new = 'new';
            $groupName = (isset($params['group']) ? $params['group']['name'] : '<undefined>');
            $actionName = $this->_request->getActionName();

            switch ($params['store_action']) {
                //
                // Handle saving of existing Website
                //
                case 'edit':
                    // Adapt log vars
                    $new = 'existing';
                    $group = Mage::getModel('core/store_group')->load($params['group']['group_id']);
                    if ($group->getId()) {
                        // refer to old Group Name in case of edit
                        $groupName = $group->getName();
                    }

                    // Convert Default Store ID
                    $defaultStoreId = $params['group']['default_store_id'];
                    $defaultStore = Mage::getModel('core/store')->load($defaultStoreId);
                    $params['group']['default_store_id'] = $defaultStore->getCode();

                    // break intentionally omitted

                //
                // Handle adding new Group
                //
                case 'add':
                    // Convert Group ID
                    $website = Mage::getModel('core/website')->load($params['group']['website_id']);
                    $params['group']['group_id'] = $groupName;

                    // Convert Website ID
                    $params['group']['website_id'] = $website->getCode();

                    // Convert Root Category ID
                    $rootCategoryId = $params['group']['root_category_id'];
                    $rootCategory = Mage::getModel('catalog/category')->load($rootCategoryId);
                    $rootCategoryUuid = $helper->getCategoryUuidFromPath($rootCategory->getPath(), null, self::UUID_SEPARATOR);
                    $params['group']['root_category_id'] = $rootCategoryUuid;

                    break;

                //
                // Handle deleting existing Website
                // store_action parameter is undefined in case of delete
                //
                default:
                    // Adapt log vars and Convert Item ID
                    $new = 'existing';
                    $actionName = 'delete';
                    $group = Mage::getModel('core/store_group')->load($params['item_id']);
                    if ($group->getId()) {
                        $groupName = $group->getName();
                        $website = Mage::getModel('core/website')->load($group->getWebsiteId());
                        $params['item_id'] = $website->getCode()
                            . self::UUID_SEPARATOR . $group->getName();
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
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s Store Group '%s'", ucfirst($actionName), $new, $groupName);
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

        $helper = Mage::helper('pugmore_mageploy');
        $params = $this->_decodeParams($encodedParameters);

        switch ($params['store_action']) {
            //
            // Handle saving of existing Website
            //
            case 'edit':
                $websiteUuid = $params['group']['website_id'];
                $website = Mage::getModel('core/website')->load($websiteUuid, 'code');

                if (!$website->getId()) {
                    throw new Exception('Website \''.$websiteUuid.'\' not found!');
                }

                // Convert Default Store UUID
                $defaultStoreUuid = $params['group']['default_store_id'];
                $defaultStore = Mage::getModel('core/store')->load($defaultStoreUuid, 'code');

                if (!$defaultStore->getId()) {
                    throw new Exception('Store \''.$defaultStoreUuid.'\' not found!');
                }

                $params['group']['default_store_id'] = $defaultStore->getId();

                // Convert Group UUID
                $groupUuid = $params['group']['group_id'];
                $group = Mage::getModel('core/store_group')
                    ->getCollection()
                    ->addWebsiteFilter($website->getId())
                    ->addFieldToFilter('name', $groupUuid)
                    ->getFirstItem();

                if (!$group->getId()) {
                    throw new Exception('Group \''.$groupUuid.'\' not found!');
                }

                $params['group']['group_id'] = $group->getId();

                // break intentionally omitted

            //
            // Handle adding new Website
            //
            case 'add':
                $websiteUuid = $params['group']['website_id'];
                if (!isset($website)) {
                    $website = Mage::getModel('core/website')->load($websiteUuid, 'code');
                }

                if (!$website->getId()) {
                    throw new Exception('Website \''.$websiteUuid.'\' not found!');
                }

                // Convert Website UUID
                $params['group']['website_id'] = $website->getId();

                // Convert Root Category UUID
                $rootCategoryUuid = $params['group']['root_category_id'];
                $rootCategoryPath = $helper->getCategoryPathFromUuid($rootCategoryUuid, self::UUID_SEPARATOR);
                $rootCategoryId = $helper->getCategoryIdFromPath($rootCategoryPath);
                $params['group']['root_category_id'] = $rootCategoryId;

                break;

            //
            // Handle deleting existing Website
            // store_action parameter is undefined in case of delete
            //
            default:
                //Convert Item UUID
                $itemUuid = $params['item_id'];
                list($websiteCode, $groupName) = explode(self::UUID_SEPARATOR, $itemUuid, 2);
                $website = Mage::getModel('core/website')->load($websiteCode, 'code');
                if (!$website->getId()) {
                    throw new Exception('Website \''.$websiteCode.'\' not found!');
                }
                $group = Mage::getModel('core/store_group')
                    ->getCollection()
                    ->addWebsiteFilter($website->getId())
                    ->addFieldToFilter('name', $groupName)
                    ->getFirstItem();
                break;
        }

        $request = new Mage_Core_Controller_Request_Http();
        $request->setPost($params);
        $_SERVER['REQUEST_METHOD'] = 'POST'; // needed by StoreController
        $request->setQuery($params);
        return $request;
    }

}
