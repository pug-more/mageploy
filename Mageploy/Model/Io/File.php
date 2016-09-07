<?php
/**
 * Description of File
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Io_File implements PugMoRe_Mageploy_Model_Io_RecordingInterface {

    private $_todo;
    private $_done;
    protected $_helper;

    public function __construct()
    {
        $this->_helper = Mage::helper('pugmore_mageploy');
        $this->createStoragePath();
        $this->_todo = $this->getAllActionsFilepath();
        $this->_done = $this->getExecutedActionsFilepath();
    }

    protected function createStoragePath()
    {
        $storagePath = is_dir($this->_helper->getStoragePath());
        if (! $storagePath) {
            $storagePath = mkdir($this->_helper->getStoragePath(), 0755, true);
            if (false === $storagePath) {
                Mage::logException(new Exception(sprintf("Can't create folder '%s'", $this->_helper->getStoragePath())));
            }
        }
        return $storagePath;
    }

    protected function getAllActionsFilepath()
    {
        $storagePath = $this->_helper->getStoragePath();
        $filepath = @fopen($storagePath . $this->_helper->getAllActionsFilename(), 'a');
        if (false === $filepath) {
            Mage::logException(new Exception(sprintf("Can't open file '%s'", $this->_helper->getAllActionsFilename())));
        }

        return $filepath;
    }

    protected function getExecutedActionsFilepath()
    {
        $storagePath = $this->_helper->getStoragePath();
        $filepath = @fopen($storagePath . $this->_helper->getExecutedActionsFilename(), 'a');
        if (false === $filepath) {
            Mage::logException(new Exception(sprintf("Can't open file '%s'", $this->_helper->getExecutedActionsFilename())));
        }
        return $filepath;
    }
    
    public function canRecord()
    {
        $storagePath = $this->createStoragePath();

        if (! $storagePath) {
            return false;
        }
        
        if (false === $this->getAllActionsFilepath($storagePath)) {
            return false;
        }
        
        if (false === $this->getExecutedActionsFilepath($storagePath)) {
            return false;
        }
        
        return true;
    }

    public function record($stream) {
        if ((false === $this->_todo) || (false === $this->_done) ) {
            return;
        }

        fputcsv($this->_todo, $stream);
        fputcsv($this->_done, $stream);
    }

    public function done($stream) {
        if (false === $this->_done) {
            return;
        }

        fputcsv($this->_done, $stream);
    }

    public function getHistoryList($limit = null) {
        $csv = new Varien_File_Csv();
        $historyList = array();
        try {
            $historyList = $csv->getData($this->_helper->getStoragePath().$this->_helper->getAllActionsFilename());
            if ($count = count($historyList)) {
                if ($limit && $count > $limit) {
                    $historyList = array_slice($historyList, $count-$limit, $count, true);
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
