<?php

/**
 * @var $this Mage_Core_Model_Resource_Setup
 */
$this->startSetup();

$table = $this->getConnection()
    ->newTable($this->getTable('mageploy_executed'))
    ->addColumn('id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array('auto_increment' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true), 'ID')
    ->addColumn('executed', Varien_Db_Ddl_Table::TYPE_VARCHAR, 18, array(), 'Executed actions')
    ->setComment('Mageploy Executed Tasks');
$this->getConnection()->createTable($table);

$this->endSetup();
