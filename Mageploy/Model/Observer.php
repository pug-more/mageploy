<?php
/**
 * Mageploy Observer model
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Observer
{

    /**
     * Observes the controller_action_predispatch event
     */
    public function observeRequest($observer)
    {
        $helper = Mage::helper('pugmore_mageploy');
        if (!$helper->isActive()) {
            return;
        }

        $request = Mage::app()->getRequest();
        /** @var PugMoRe_Mageploy_Model_Request_Funnel $funnel */
        $funnel = Mage::getModel('pugmore_mageploy/request_funnel');
        $funnel
            ->init(Mage::getSingleton('pugmore_mageploy/io_file'))
            ->dispatch($request);
    }

}