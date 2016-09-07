<?php

class PugMoRe_Mageploy_Block_Adminhtml_Notifications extends Mage_Adminhtml_Block_Template
{
    /** @var  PugMoRe_Mageploy_Helper_Data */
    protected $_helper;

    protected function _construct()
    {
        $this->_helper = Mage::helper('pugmore_mageploy');
        parent::_construct();
    }

    protected function _getAllowSymlinks()
    {
        // Force template to be found if deployed with mageploy
        return true;
    }

    protected function _isActive() {
        return $this->_helper->isActive();
    }

    protected function _isAnonymous() {
        return $this->_helper->isAnonymousUser();
    }

    protected function _getAllActionsCount() {
        return $this->_helper->getAllActionsCount();
    }

    protected function _getPendingActionsCount() {
        return $this->_helper->getPendingActionsCount();
    }

    protected function _canRecord() {
        return Mage::getSingleton('pugmore_mageploy/io_file')->canRecord();
    }

}