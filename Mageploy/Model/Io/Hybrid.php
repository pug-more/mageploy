<?php
/**
 * Description of File
 *
 * @author Alessandro Ronchi <aronchi at webgriffe.com>
 */
class PugMoRe_Mageploy_Model_Io_Hybrid implements PugMoRe_Mageploy_Model_Io_RecordingInterface {

    protected $_helper;

    public function __construct()
    {
        $this->_helper = Mage::helper('pugmore_mageploy');

        if (!is_dir($this->_helper->getStoragePath())) {
            mkdir($this->_helper->getStoragePath(), 0775, true)
            or die(sprintf("Can't create folder '%s'", $this->_helper->getStoragePath()));
        }
    }

    public function record($stream) {
        $uuid = $stream[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_ACTION_UUID];
        $filename = $stream[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_ACTION_TIMESTAMP] . '-' . $uuid;
        $file = fopen($this->_helper->getStoragePath() . $filename . '.mageploy.php', 'w');
        fwrite($file, '<?php // Mageploy recorded action - edit with care' . PHP_EOL);
        fwrite($file, '$actions[\'' . $uuid . '\'] = ' . PHP_EOL);
        fwrite($file, var_export($stream, true) . ';');
        $this->done($stream);
    }

    public function getHash($stream)
    {
        return sha1(serialize($stream));
    }

    public function done($stream) {
        $executed = new PugMoRe_Mageploy_Model_Executed();
        $executed->setExecuted($stream[PugMoRe_Mageploy_Model_Action_Abstract::INDEX_ACTION_UUID]);
        $executed->save();
    }

    /**
     * Get all recorded actions
     *
     * @param integer $limit FIXME not implemented
     * @return array
     */
    public function getHistoryList($limit = null) {
        $actions = array();
        foreach (glob($this->_helper->getStoragePath() . '*.mageploy.php') as $filename)
        {
            include $filename;
        }

        return $actions;
    }

    /**
     * Get list of actions still to be executed
     *
     * @return array
     */
    public function getPendingList() {
        $pendingList = array();
        try {
            $todoList = $this->getHistoryList();
            $doneList = array();
            foreach(Mage::getModel('pugmore_mageploy/executed')->getCollection() as $doneItem) {
                $doneList[$doneItem->getExecuted()] = true;
            }
            foreach ($todoList as $k=>$v) {
                if (!isset($doneList[$k])) {
                    $pendingList[$k] = $v;
                }

            }
        } catch (Exception $e) {
            $this->_helper->log($e->getMessage());
        }
        return $pendingList;
    }

}
