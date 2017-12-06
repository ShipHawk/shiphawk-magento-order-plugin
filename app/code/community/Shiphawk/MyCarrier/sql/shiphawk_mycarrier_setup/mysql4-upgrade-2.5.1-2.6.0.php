<?php
$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_discount_percentage');
if (!$attr->getId()){
    $installer->addAttribute('catalog_product','shiphawk_discount_percentage', array (
        'attribute_set'     => 'Default',
        'group'             => 'ShipHawk Attributes',
        'label'             => 'Markup or Discount Percentage',
        'visible'           => true,
        'type'              => 'varchar',
        'apply_to'          => 'simple',
        'input'             => 'text',
        'system'            => false,
        'required'          => false,
        'user_defined'      => 1,
        'note'              => 'possible values from -100 to 100'
    ));
    /* for sortOrder */
    $installer->updateAttribute('catalog_product', 'shiphawk_discount_percentage', 'frontend_label', 'Markup or Discount Percentage', 7);
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_discount_fixed');
if (!$attr->getId()){
    $installer->addAttribute('catalog_product','shiphawk_discount_fixed', array (
        'attribute_set'     =>  'Default',
        'group'             => 'ShipHawk Attributes',
        'label'             => 'Markup or Discount Flat Amount',
        'visible'           => true,
        'type'              => 'varchar',
        'apply_to'          => 'simple',
        'input'             => 'text',
        'system'            => false,
        'required'          => false,
        'user_defined'      => 1,
        'note'              => 'possible values from -∞ to ∞'
    ));
    /* for sortOrder */
    $installer->updateAttribute('catalog_product', 'shiphawk_discount_fixed', 'frontend_label', 'Markup or Discount Flat Amount', 7);
}

$installer->endSetup();
