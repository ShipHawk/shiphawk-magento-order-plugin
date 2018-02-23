<?php

function addAttributesForFirstItem($installer){

  $label_prefix = 'Item #1 ';

  $installer->addAttribute('catalog_product', 'shiphawk_item_weight', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Weight (lbs)',
      'visible'        => true,
      'type'           => 'int',
      'apply_to'       => 'simple',
      'input'          => 'text',
      'default'        => 1,
      'frontend_class' => 'validate-not-negative-number',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Weight (lbs)'
  ));

  $installer->addAttribute('catalog_product', 'shiphawk_item_req_crating', array(
      'attribute_set'    =>  'Default',
      'group'            => 'ShipHawk Attributes',
      'backend'          => 'catalog/product_attribute_backend_msrp',
      'label'            => $label_prefix . 'Requires crating?',
      'input'            => 'select',
      'source'           => 'catalog/product_attribute_source_msrp_type_enabled',
      'type'             => 'varchar',
      'apply_to'         => 'simple',
      'visible'          => true,
      'required'         => false,
      'user_defined'     => 1,
      'default'          => '2',
      'input_renderer'   => 'adminhtml/catalog_product_helper_form_msrp_enabled',
      'visible_on_front' => false,
      'frontend_label'   => $label_prefix . 'Requires crating?'
  ));

  $installer->addAttribute('catalog_product', 'shiphawk_item_cuspak_price', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Custom Packing Price',
      'visible'        => true,
      'type'           => 'varchar',
      'apply_to'       => 'simple',
      'frontend_class' => 'validate-number',
      'input'          => 'text',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Custom Packing Price'
  ));

}

function addAttributesForItemNumber($installer, $itemNumber){

  $prefix = "shiphawk_item_{$itemNumber}_";
  $label_prefix = "Item #{$itemNumber} ";

  $installer->addAttribute('catalog_product', $prefix . 'type', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Type of Item',
      'visible'        => true,
      'type'           => 'varchar',
      'apply_to'       => 'simple',
      'input'          => 'text',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Type of Item'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'type_id', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Type of Item',
      'visible'        => true,
      'type'           => 'varchar',
      'apply_to'       => 'simple',
      'input'          => 'text',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Type of Item'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'quantity', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Quantity',
      'visible'        => true,
      'type'           => 'int',
      'apply_to'       => 'simple',
      'input'          => 'text',
      'frontend_class' => 'validate-not-negative-number',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Quantity'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'weight', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Weight (lbs)',
      'visible'        => true,
      'type'           => 'int',
      'apply_to'       => 'simple',
      'input'          => 'text',
      'frontend_class' => 'validate-not-negative-number',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Weight (lbs)'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'is_packed', array(
      'attribute_set'    =>  'Default',
      'group'            => 'ShipHawk Attributes',
      'backend'          => 'catalog/product_attribute_backend_msrp',
      'label'            => $label_prefix . 'Is Item Already Packed?',
      'input'            => 'select',
      'source'           => 'catalog/product_attribute_source_msrp_type_enabled',
      'type'             => 'varchar',
      'apply_to'         => 'simple',
      'visible'          => true,
      'required'         => false,
      'user_defined'     => 1,
      'default'          => '2',
      'input_renderer'   => 'adminhtml/catalog_product_helper_form_msrp_enabled',
      'visible_on_front' => false,
      'frontend_label'   => $label_prefix . 'Is Item Already Packed?'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'req_crating', array(
      'attribute_set'    =>  'Default',
      'group'            => 'ShipHawk Attributes',
      'backend'          => 'catalog/product_attribute_backend_msrp',
      'label'            => $label_prefix . 'Requires crating?',
      'input'            => 'select',
      'source'           => 'catalog/product_attribute_source_msrp_type_enabled',
      'type'             => 'varchar',
      'apply_to'         => 'simple',
      'visible'          => true,
      'required'         => false,
      'user_defined'     => 1,
      'default'          => '2',
      'input_renderer'   => 'adminhtml/catalog_product_helper_form_msrp_enabled',
      'visible_on_front' => false,
      'frontend_label'   => $label_prefix . 'Requires crating?'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'length', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Length',
      'visible'        => true,
      'type'           => 'varchar',
      'apply_to'       => 'simple',
      'input'          => 'text',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Length'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'width', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Width',
      'visible'        => true,
      'type'           => 'varchar',
      'apply_to'       => 'simple',
      'input'          => 'text',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Width'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'height', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Height',
      'visible'        => true,
      'type'           => 'varchar',
      'apply_to'       => 'simple',
      'input'          => 'text',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Height'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'cuspak_price', array(
      'attribute_set'  =>  'Default',
      'group'          => 'ShipHawk Attributes',
      'label'          => $label_prefix . 'Custom Packing Price',
      'visible'        => true,
      'type'           => 'varchar',
      'apply_to'       => 'simple',
      'frontend_class' => 'validate-number',
      'input'          => 'text',
      'system'         => false,
      'required'       => false,
      'user_defined'   => 1,
      'frontend_label' => $label_prefix . 'Custom Packing Price'
  ));

  $installer->addAttribute('catalog_product', $prefix . 'freight_class', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => $label_prefix . 'Freight Class',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'option'        => array('values' => array(
            0 => '50',
            1 => '55',
            2 => '60',
            3 => '65',
            4 => '70',
            5 => '77.5',
            6 => '85',
            7 => '92.5',
            8 => '100',
            9 => '110',
            10 => '125',
            11 => '150',
            12 => '175',
            13 => '200',
            14 => '250',
            15 => '300',
            16 => '400',
            17 => '500'
        )),
        'input'          => 'select',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'frontend_label' => $label_prefix + 'Freight Class'
    ));

}


$installer = Mage::getResourceModel('sales/setup','sales_setup');
$installer->startSetup();

addAttributesForFirstItem($installer);

$itemNumbers = array(2, 3, 4, 5, 6, 7, 8, 9, 10);
foreach ($itemNumbers as &$itemNumber) {
  addAttributesForItemNumber($installer, $itemNumber);
}

$installer->endSetup();