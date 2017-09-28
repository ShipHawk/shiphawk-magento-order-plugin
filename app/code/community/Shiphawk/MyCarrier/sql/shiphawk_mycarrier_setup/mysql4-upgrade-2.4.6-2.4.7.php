<?php

$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();

$incorrect_attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', ' shiphawk_height');
if ($incorrect_attr->getId()) {
  $correct_attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_height');

  if ($correct_attr->getId()) {
    $installer->updateAttribute('catalog_product', ' shiphawk_height',    'attribute_code', 'old_shiphawk_height');
    $installer->updateAttribute('catalog_product', 'old_shiphawk_height', 'label',          'Old Height');
  } else {
    $installer->updateAttribute('catalog_product', ' shiphawk_height', 'attribute_code', 'shiphawk_height');
  }
}
$installer->endSetup();
