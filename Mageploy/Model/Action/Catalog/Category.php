<?php

/**
 * Description of Category
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Action_Catalog_Category extends PugMoRe_Mageploy_Model_Action_Abstract {

    protected $_code = 'catalog_category';
    protected $_blankableParams = array('key', 'isAjax', 'isIframe', 'form_key',
        'active_tab_id', 'page', 'limit', 'in_category', 'entity_id', 'name', 'sku', 'price', 'position');

    protected function _getCategoryUuidFromPath($path, $append = null) {
        $categoryUuidParts = array();
        $categoryIdParts = split('/', $path);
        foreach ($categoryIdParts as $i => $catId) {
            if ($i == 0) {
                $categoryUuidParts[] = Mage_Catalog_Model_Category::TREE_ROOT_ID;
            } else {
                $categoryUuidParts[] = Mage::getModel('catalog/category')->load($catId)->getName();
            }
        }
        if ($append) {
            $categoryUuidParts[] = $append;
        }
        return join(self::UUID_SEPARATOR, $categoryUuidParts);
    }

    public function match() {
        if (!$this->_request) {
            return false;
        }

        if ($this->_request->getModuleName() == 'admin') {
            if ($this->_request->getControllerName() == 'catalog_category') {
                if (in_array($this->_request->getActionName(), array('save', 'delete'))) {
                    return true;
                }
            }
        }

        return false;
    }

    /*
     *  We are assuming that siblings will never have the same name.
     *  @todo explore the possibility to use urls as UUIDs
     */

    public function encode() {
        $result = parent::encode();

        if ($this->_request) {
            $params = $this->_request->getParams();

            $newOrExisting = '';
            if (isset($params['id'])) {
                $categoryId = $params['id'];
                $newOrExisting = 'existing';
                $path = $params['general']['path'];
                $categoryUuid = $this->_getCategoryUuidFromPath($path);
            } else {
                $categoryId = false;
                $newOrExisting = 'new';
                $parentId = $params['general']['path'];
                $parentCategory = Mage::getModel('catalog/category')->load($parentId);
                $path = $parentCategory->getPath();
                $categoryUuid = $this->_getCategoryUuidFromPath($path, $params['general']['name']);
            }

            $params['id'] = $categoryUuid;

            $storeId = $params['store'];
            if ($storeId) {
                $storeUuid = Mage::app()->getStore($storeId)->getCode();
            } else {
                $storeUuid = $storeId;
            }
            $params['store'] = $storeUuid;

            $parentId = $params['parent'];
            if ($parentId) {
                $parentCategory = Mage::getModel('catalog/category')->load($parentId);
                $parentUuid = $this->_getCategoryUuidFromPath($parentCategory->getPath());
            } else {
                $parentUuid = $parentId;
            }
            $params['parent'] = $parentUuid;
            
            // @todo $params['category_products']
            $associatedProductIds = split('&', $params['category_products']);
            $associatedProductUuids = array();
            foreach ($associatedProductIds as $i => $association) {
                list($id, $position) = explode('=', $association);
                $prod = Mage::getModel('catalog/product')->load($id);
                $associatedProductUuids[] = sprintf("%s=%d", $prod->getSku(), $position);
            }
            $params['category_products'] = join('&', $associatedProductUuids);
            
            foreach ($this->_blankableParams as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                }
            }

            $result[self::INDEX_EXECUTOR_CLASS] = get_class($this);
            $result[self::INDEX_CONTROLLER_MODULE] = $this->_request->getControllerModule();
            $result[self::INDEX_CONTROLLER_NAME] = $this->_request->getControllerName();
            $result[self::INDEX_ACTION_NAME] = $this->_request->getActionName();
            $result[self::INDEX_ACTION_PARAMS] = serialize($params);
            $result[self::INDEX_ACTION_DESCR] = sprintf("%s %s Category with UUID '%s'", ucfirst($this->_request->getActionName()), $newOrExisting, $categoryUuid);
        } else {
            $result = false;
        }
        return $result;
    }

}