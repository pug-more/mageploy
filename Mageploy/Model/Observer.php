<?php
/**
 * Mageploy Observer model
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Observer {

    public function observeRequest($observer) {
        $request = Mage::app()->getRequest();
        $funnel = Mage::getModel('pugmore_mageploy/request_funnel')
                ->init(Mage::getSingleton('pugmore_mageploy/io_file'))
                ->dispatch($request);
    }

}