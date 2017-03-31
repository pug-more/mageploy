<?php
/**
 * Created by PhpStorm.
 * User: marco
 * Date: 07/05/15
 * Time: 12.01
 */
/* @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

$tableExecuted = $installer->getConnection()->newTable($installer->getTable('pugmore_mageploy/executed'))
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
    'unsigned' => true,
    'primary'  => true,
    'identity'  => true,
    'nullable'  => false
), 'Entity ID')
    ->addColumn('action', Varien_Db_Ddl_Table::TYPE_TEXT, null, array(
        ), 'Actions');

$installer->getConnection()->createTable($tableExecuted);


$installer->endSetup();