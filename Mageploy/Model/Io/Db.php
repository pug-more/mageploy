<?php
/**
 * Description of File
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Io_Db implements PugMoRe_Mageploy_Model_Io_RecordingInterface {

    private $_todo;
    private $_done;
    protected $_helper;

    public function __construct()
    {
        $this->_helper = Mage::helper('pugmore_mageploy');
        $this->createStoragePath();
        $this->_todo = $this->getAllActionsFilepath();
        $this->_done = $this->getExecutedActions();
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

    protected function getExecutedActions()
    {
        return Mage::getModel('pugmore_mageploy/executed')->getCollection();
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
               $doneList = $this->getExecutedActions();
               $todo = array();
               $done = array();
               foreach ($todoList as $k=>$v) {
                   $todo[] = implode('|',$v);
               }
               foreach ($doneList as $k => $action) {
                   $done[] = $action->getAction();
               }

               foreach ( array_diff ($todo, $done) as $k=>$v) {
                   $pendingList[$k] = $todoList[$k];
               }

           } catch (Exception $e) {
               $this->_helper->log($e->getMessage());
           }
           return $pendingList;
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

        return true;
    }


    /**
     * Record given stream of data in both global and local actions registry.
     *
     * @param mixed $stream
     * @return mixed
     */
    public function record($stream)
    {
        if ((false === $this->_todo) || (false === $this->_done) ) {
            return;
        }
        $connection = Mage::getSingleton('core/resource')->getConnection('core_write');

        fputcsv($this->_todo, $stream);
        try{
            $connection->beginTransaction();
            $action = Mage::getModel('pugmore_mageploy/executed');
            $action->setEntityId($stream[0])
                ->setAction(implode("|",$stream))->save();
            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            $this->_helper->log($e->getMessage());
            throw $e;
        }

    }

    /**
     * Record given stream of data in local actions registry.
     *
     * @param mixed $stream
     * @return mixed
     */
    public function done($stream)
    {
        if (false === $this->_done) {
            return;
        }
        Mage::getModel('pugmore_mageploy/executed')->setEntityId($stream[0])->setAction(implode('|',$stream))->save();
    }
}
