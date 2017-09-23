<?php

$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();

$installer->updateAttribute('catalog_product', 'shiphawk_type_of_product',       'label', 'Type of Item',                1);
$installer->updateAttribute('catalog_product', 'shiphawk_quantity',              'label', 'Number of items per Product', 2);
$installer->updateAttribute('catalog_product', 'shiphawk_item_is_packed',        'label', 'Is Product Already Packed?',  3);
$installer->updateAttribute('catalog_product', 'shiphawk_length',                'label', 'Length',                      4);
$installer->updateAttribute('catalog_product', 'shiphawk_width',                 'label', 'Width',                       5);
$installer->updateAttribute('catalog_product', 'shiphawk_height',                'label', 'Height',                      6);
$installer->updateAttribute('catalog_product', 'shiphawk_carrier_type',          'label', 'Carrier Type',                7);
$installer->updateAttribute('catalog_product', 'shiphawk_freight_class',         'label', 'Freight Class',               8);
$installer->updateAttribute('catalog_product', 'shiphawk_item_value',            'label', 'Item Value',                  9);
$installer->updateAttribute('catalog_product', 'shiphawk_type_of_product_value', 'label', 'Origin Contact:',             10);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_firstname',      'label', 'Origin First Name',           11);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_lastname',       'label', 'Origin Last Name',            12);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_addressline1',   'label', 'Origin Address',              13);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_addressline2',   'label', 'Origin Address 2',            14);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_city',           'label', 'Origin City',                 15);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_state',          'label', 'State',                       16);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_zipcode',        'label', 'Origin Zipcode',              17);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_phonenum',       'label', 'Origin Phone',                18);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_email',          'label', 'Origin Email',                19);
$installer->updateAttribute('catalog_product', 'shiphawk_origin_location',       'label', 'Origin Location',             20);

$installer->endSetup();
