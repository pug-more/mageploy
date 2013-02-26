<?php
/**
 * Description of File
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Io_File {

    private $_todo;
    private $_done;
    protected $_helper;

    public function __construct()
    {
        $this->_helper = Mage::helper('pugmore_mageploy');
        
        if (!is_dir($this->_helper->getStoragePath())) {
            mkdir($this->_helper->getStoragePath(), 0755, true)
                or die(sprintf("Can't create folder '%s'", $this->_helper->getStoragePath()));
        }

        $this->_todo = fopen($this->_helper->getStoragePath().$this->_helper->getAllActionsFilename(), 'a')
            or die(sprintf("Can't open file '%s'", $this->_helper->getAllActionsFilename()));
        
        $this->_done = fopen($this->_helper->getStoragePath().$this->_helper->getExecutedActionsFilename(), 'a')
            or die(sprintf("Can't open file '%s'", $this->_helper->getExecutedActionsFilename()));
    }

    public function record($stream) {
        fputcsv($this->_todo, $stream);
        fputcsv($this->_done, $stream);
    }

    public function done($stream) {
        fputcsv($this->_done, $stream);
    }

    public function getHistoryList($limit) {
        $csv = new Varien_File_Csv();
        try {
            $historyList = $csv->getData($this->_helper->getStoragePath().$this->_helper->getAllActionsFilename());
            if ($count = count($historyList)) {
                if ($limit && $count > $limit) {
                    $historyList = array_slice($historyList, $limit, $count, true);
                }
            }
        } catch (Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return $historyList;
    }

    public function getPendingList() {
        $csv = new Varien_File_Csv();
        $pendingList = array();
        try {
            $todoList = $csv->getData($this->_helper->getStoragePath().$this->_helper->getAllActionsFilename());
            $doneList = $csv->getData($this->_helper->getStoragePath().$this->_helper->getExecutedActionsFilename());
            $todo = array();
            $done = array();
            foreach ($todoList as $k=>$v) {
                $todo[$k] = implode('|',$v);
            }
            foreach ($doneList as $k=>$v) {
                $done[$k] = implode('|',$v);
            }
            foreach ( array_diff ($todo, $done) as $k=>$v) {
                $pendingList[$k] = $todoList[$k];
            }
        } catch (Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return $pendingList;
    }

}
