<?php
$installer = Mage::getResourceModel('sales/setup','sales_setup');
$installer->startSetup();

$installer->addAttribute('order', 'shiphawk_order_id', array('type' => 'text', 'input' => 'text'));
$installer->getConnection()->addColumn($installer->getTable('sales_flat_order'), 'shiphawk_order_id', 'text');
$installer->getConnection()->addColumn($installer->getTable('sales_flat_quote'), 'shiphawk_order_id', 'text');

$installer->endSetup();
