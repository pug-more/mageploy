<?php
/**
 * Description of Funnel
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Request_Funnel {

    const XML_ACTIONS_PATH = 'default/mageploy/actions';    
    
    protected $_actions = array();
    
    protected $_io;
    
    public function addAction($actionCode, $action) {
        $this->_actions[$actionCode] = $action;
        return $this;
    }
    
    public function getActions() {
        return $this->_actions;
    }
    
    public function init($io) {
        $this->_io = $io;

        Mage::dispatchEvent('mageploy_funnel_collect_actions_before', array('funnel'=>$this));
        
        $actionsInfo =  Mage::getConfig()->getNode(self::XML_ACTIONS_PATH)->asArray();

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
        
        Mage::log(sprintf("Should record '%s'", $action->toString()), null, 'mageploy.log', true);
        $this->_io->record($action->encode());
        
        Varien_Profiler::stop('mageploy::funnel::record_action');

        Mage::dispatchEvent('mageploy_funnel_record_action_after', array('funnel'=>$this, 'action'=>$action));
        
        return $this;
    }

}