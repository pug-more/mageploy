<?php
/**
 * Description of File
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Io_File {
    
    private $_todo;
    private $_done;
    
    public function __construct()
    {
        $helper = Mage::helper('pugmore_mageploy');
        $this->_todo = fopen($helper->getStoragePath().$helper->getAllActionsFilename(), 'a');
        $this->_done = fopen($helper->getStoragePath().$helper->getExecutedActionsFilename(), 'a');
        //$this->_done = fopen(Mage::getBaseDir().DS.'done.csv', 'a');
    }
    
    public function record($stream) {
        fputcsv($this->_todo, $stream);  
        fputcsv($this->_done, $stream);
    }
    
}
