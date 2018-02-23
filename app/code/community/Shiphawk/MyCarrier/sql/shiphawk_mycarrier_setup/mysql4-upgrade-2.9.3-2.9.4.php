<?php

function orderAttributes($installer){
  $attributes = array(
    // Origin
    "shiphawk_origin_firstname"    => "Origin First Name",
    "shiphawk_origin_lastname"     => "Origin Last Name",
    "shiphawk_origin_addressline1" => "Origin Address",
    "shiphawk_origin_addressline2" => "Origin Address 2",
    "shiphawk_origin_city"         => "Origin City",
    "shiphawk_origin_state"        => "Origin State",
    "shiphawk_origin_zipcode"      => "Origin Zipcode",
    "shiphawk_origin_phonenum"     => "Origin Phone",
    "shiphawk_origin_email"        => "Origin Email",
    "shiphawk_origin_location"     => "Origin Location Type",

    // Markups
    "shiphawk_discount_fixed"      => "Markup or Discount Flat Amount",
    "shiphawk_discount_percentage" => "Markup or Discount Percentage",

    // SKU Only Attributes
    "shiphawk_item_value"   => "Product Value", // the value of SKU
    "shiphawk_carrier_type" => "Carrier Type",

    // Item 1
    // This will reuse existing attributes, but give the new labels, so it consistent with added items
    "shiphawk_type_of_product"       => "Item #1 Type", // This attribute stores item type name
    "shiphawk_type_of_product_value" => "Item #1 Type id", // Label is not shown, This attribute stores item type id
    "shiphawk_quantity"              => "Item #1 Quantity",
    "shiphawk_item_weight"           => "Item #1 Weight (lbs)",
    "shiphawk_item_is_packed"        => "Item #1 Already Packed?",
    "shiphawk_item_req_crating"      => "Item #1 Requires crating?", # NEW Attribute
    "shiphawk_length"                => "Item #1 Length",
    "shiphawk_width"                 => "Item #1 Width",
    "shiphawk_height"                => "Item #1 Height",
    "shiphawk_item_cuspak_price"     => "Item #1 Custom Packing Price", # NEW Attribute
    "shiphawk_freight_class"         => "Item #1 Freight Class",
  );

  $itemNumbers = array(2, 3, 4, 5, 6, 7, 8, 9, 10);
  foreach ($itemNumbers as $itemNumber) {
    $attributes["shiphawk_item_{$itemNumber}_type"]          = "Item #{$itemNumber} Type";
    $attributes["shiphawk_item_{$itemNumber}_type_id"]       = "Item #{$itemNumber} Type id";
    $attributes["shiphawk_item_{$itemNumber}_quantity"]      = "Item #{$itemNumber} Quantity";
    $attributes["shiphawk_item_{$itemNumber}_weight"]        = "Item #{$itemNumber} Weight (lbs)";
    $attributes["shiphawk_item_{$itemNumber}_is_packed"]     = "Item #{$itemNumber} Already Packed?";
    $attributes["shiphawk_item_{$itemNumber}_req_crating"]   = "Item #{$itemNumber} Requires crating?";
    $attributes["shiphawk_item_{$itemNumber}_length"]        = "Item #{$itemNumber} Length";
    $attributes["shiphawk_item_{$itemNumber}_width"]         = "Item #{$itemNumber} Width";
    $attributes["shiphawk_item_{$itemNumber}_height"]        = "Item #{$itemNumber} Height";
    $attributes["shiphawk_item_{$itemNumber}_cuspak_price"]  = "Item #{$itemNumber} Custom Packing Price";
    $attributes["shiphawk_item_{$itemNumber}_freight_class"] = "Item #{$itemNumber} Freight Class";
  }

  $i = 1;
  foreach ($attributes as $code => $label) {
    $installer->updateAttribute('catalog_product', $code, 'label', $label, $i);
    $installer->updateAttribute('catalog_product', $code, 'frontend_label', $label);
    $i++;
  }
}


$installer = Mage::getResourceModel('sales/setup','sales_setup');
$installer->startSetup();
orderAttributes($installer);
$installer->endSetup();