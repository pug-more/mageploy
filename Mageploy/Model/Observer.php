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

        /** @var PugMoRe_Mageploy_Model_Io_RecordingInterface $recorder */
        if($helper->useDb()){
            $recorder = Mage::getSingleton('pugmore_mageploy/io_db');
        }else{
            $recorder = Mage::getSingleton('pugmore_mageploy/io_file');
        }

        /** @var Mage_Core_Model_Config $config */
        $config = Mage::getConfig();

        /** @var Mage_Core_Controller_Request_Http $request */
        $request = Mage::app()->getRequest();

        /** @var PugMoRe_Mageploy_Model_Request_Funnel $funnel */
        $funnel = Mage::getModel('pugmore_mageploy/request_funnel');
        $funnel
            ->init($recorder, $config)
            ->dispatch($request);
    }

}