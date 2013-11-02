<?php
/**
 * Description of Funnel
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Request_Funnel {

    const XML_ACTIONS_PATH = 'default/mageploy/actions';    
    
    protected $_actions = array();

    /** @var  PugMoRe_Mageploy_Model_Io_RecordingInterface */
    protected $_io;

    /** @var  Mage_Core_Model_Config */
    protected $_config;
    
    public function addAction($actionCode, $action) {
        $this->_actions[$actionCode] = $action;
        return $this;
    }
    
    public function getActions() {
        return $this->_actions;
    }

    /**
     * @param PugMoRe_Mageploy_Model_Io_RecordingInterface $io
     * @param Mage_Core_Model_Config $config
     * @return PugMoRe_Mageploy_Model_Request_Funnel $this
     */
    public function init($io, $config) {
        $this->_io = $io;
        $this->_config = $config;

        Mage::dispatchEvent('mageploy_funnel_collect_actions_before', array('funnel'=>$this));
        
        $actionsInfo =  $this->_config->getNode(self::XML_ACTIONS_PATH)->asArray();

        Varien_Profiler::start('mageploy::funnel::collect_actions');
        foreach ($actionsInfo as $actionCode => $actionInfo) {
            if (isset($actionInfo['disabled']) && $actionInfo['disabled']) {
                continue;
            }
            if (isset($actionInfo['class'])) {
                $action = new $actionInfo['class'];
                $this->addAction($actionCode, $action);
            }
        }
        Varien_Profiler::stop('mageploy::funnel::collect_actions');

        Mage::dispatchEvent('mageploy_funnel_collect_actions_after', array('funnel'=>$this));
        
        return $this;
    }
    
    public function dispatch($request)
    {
        foreach ($this->getActions() as $action) {
            if ($action->setRequest($request)->match()) {
                $this->record($action);
            }
        }
    }
    
    public function record($action) {
        Mage::dispatchEvent('mageploy_funnel_record_action_before', array('funnel'=>$this, 'action'=>$action));
        
        Varien_Profiler::start('mageploy::funnel::record_action');
        
        Mage::helper('pugmore_mageploy')->log("Should record '%s'", $action->toString());
        $result = $action->encode();
        ksort($result);
        $this->_io->record($result);
        
        Varien_Profiler::stop('mageploy::funnel::record_action');

        Mage::dispatchEvent('mageploy_funnel_record_action_after', array('funnel'=>$this, 'action'=>$action));
        
        return $this;
    }

}