<?php
/**
 * Description of File
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Io_Hybrid implements PugMoRe_Mageploy_Model_Io_RecordingInterface {

    private $_todo;
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
    }

    public function record($stream) {
        fputcsv($this->_todo, $stream);
        $this->done($stream);
    }

    public function getHash($stream)
    {
        return sha1(serialize($stream));
    }

    public function done($stream) {
        $executed = new PugMoRe_Mageploy_Model_Executed();
        $executed->setExecuted($stream[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_ACTION_TIMESTAMP]);
        $executed->save();
    }

    public function getHistoryList($limit = null) {
        $csv = new Varien_File_Csv();
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
            $doneList = array();
            foreach(Mage::getModel('pugmore_mageploy/executed')->getCollection() as $doneItem) {
                $doneList[$doneItem->getExecuted()] = true;
            }
            foreach ($todoList as $k=>$v) {
                if (!isset($doneList[$v[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_ACTION_TIMESTAMP]])) {
                    $pendingList[$k] = $v;
                }

            }
        } catch (Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return $pendingList;
    }

}
