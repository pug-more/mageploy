<?php

/**
 * This Controller is used instead of native 
 * PugMoRe_Mageploy_Adminhtml_System_ConfigController in order to bypass ACL
 * checkings and avoid the need of authenticating an Admin user in Session.
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
require_once 'Mage/Adminhtml/controllers/System/ConfigController.php';

class PugMoRe_Mageploy_Adminhtml_System_ConfigController extends Mage_Adminhtml_System_ConfigController {

    protected function _isAllowed() {
        return true;
    }

    protected function _isSectionAllowed($section) {
        return true;
    }

}