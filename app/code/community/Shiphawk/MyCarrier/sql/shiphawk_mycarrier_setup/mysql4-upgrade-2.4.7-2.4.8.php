<?php

$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();

$installer->updateAttribute('catalog_product', 'shiphawk_carrier_type', 'backend_model', 'eav/entity_attribute_backend_array');

$installer->endSetup();
