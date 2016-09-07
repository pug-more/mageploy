<?php
interface PugMoRe_Mageploy_Model_Io_RecordingInterface {

    /**
     * Record given stream of data in both global and local actions registry.
     *
     * @param mixed $stream
     * @return mixed
     */
    public function record($stream);

    /**
     * Record given stream of data in local actions registry.
     *
     * @param mixed $stream
     * @return mixed
     */
    public function done($stream);

    /**
     * Get a list of all global registered actions.
     *
     * @param int $limit
     * @return mixed
     */
    public function getHistoryList($limit = null);

    /**
     * Get a list of all actions that still have to be executed locally.
     *
     * @return array
     */
    public function getPendingList();

    /**
     * Check if there are write issues 
     * 
     * @return boolean
     */
    public function canRecord();
}