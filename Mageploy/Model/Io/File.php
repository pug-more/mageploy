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
        $this->_todo = fopen(Mage::getBaseDir().DS.'var'.DS.'log'.DS.'todo.csv', 'a');
        $this->_done = fopen(Mage::getBaseDir().DS.'var'.DS.'log'.DS.'done.csv', 'a');
        //$this->_done = fopen(Mage::getBaseDir().DS.'done.csv', 'a');
    }
    
    public function record($stream) {
        fputcsv($this->_todo, $stream);  
        fputcsv($this->_done, $stream);
    }
    
}
