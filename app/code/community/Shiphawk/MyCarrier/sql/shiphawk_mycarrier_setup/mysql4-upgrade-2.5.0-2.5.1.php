<?php

$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();

$incorrect_attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'old_shiphawk_height');
if ($incorrect_attr->getId()) {
  $installer->removeAttribute('catalog_product', 'old_shiphawk_height');
}
$installer->endSetup();
