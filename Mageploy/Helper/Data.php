<?php
/**
 * Description of Data
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Helper_Data extends Mage_Core_Helper_Abstract {
    
    public function getStoragePath() {
        return Mage::getBaseDir().DS.'var'.DS.'log'.DS;
    }
    
    public function getExecutedActionsFilename() {
        return 'done.csv';
    }
    
    public function getAllActionsFilename() {
        return 'todo.csv';
    }
    
}
