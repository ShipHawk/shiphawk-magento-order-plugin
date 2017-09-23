<?php

$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();


$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_type_of_product');
if (!$attr->getId()){
    $installer->addAttribute('catalog_product', 'shiphawk_type_of_product', array(
        'attribute_set'  =>  'Default',
        'group'          => 'ShipHawk Attributes',
        'label'          => 'Type of Item',
        'visible'        => true,
        'type'           => 'varchar',
        'apply_to'       => 'simple',
        'input'          => 'text',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'frontend_label' => 'Type of Item'
    ));
}


$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_quantity');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_quantity', array(
        'attribute_set'  =>  'Default',
        'group'          => 'ShipHawk Attributes',
        'label'          => 'Number of items per Product',
        'visible'        => true,
        'type'           => 'int',
        'apply_to'       => 'simple',
        'input'          => 'text',
        'default'        => 1,
        'frontend_class' => 'validate-not-negative-number',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'frontend_label' => 'Number of items per Product'
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_item_is_packed');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_item_is_packed', array(
        'attribute_set'    =>  'Default',
        'group'            => 'ShipHawk Attributes',
        'backend'          => 'catalog/product_attribute_backend_msrp',
        'label'            => 'Is Product Already Packed?',
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
        'frontend_label'   => 'Is Product Already Packed?'
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_length');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_length', array(
        'attribute_set'  =>  'Default',
        'group'          => 'ShipHawk Attributes',
        'label'          => 'Length',
        'visible'        => true,
        'type'           => 'varchar',
        'apply_to'       => 'simple',
        'input'          => 'text',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'frontend_label' => 'Length'
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_width');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_width', array(
        'attribute_set'  =>  'Default',
        'group'          => 'ShipHawk Attributes',
        'label'          => 'Width',
        'visible'        => true,
        'type'           => 'varchar',
        'apply_to'       => 'simple',
        'input'          => 'text',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'frontend_label' => 'Width'
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', ' shiphawk_height');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', ' shiphawk_height', array(
        'attribute_set'  =>  'Default',
        'group'          => 'ShipHawk Attributes',
        'label'          => 'Height',
        'visible'        => true,
        'type'           => 'varchar',
        'apply_to'       => 'simple',
        'input'          => 'text',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'frontend_label' => 'Height'
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_item_value');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_item_value', array(
        'attribute_set'  =>  'Default',
        'group'          => 'ShipHawk Attributes',
        'label'          => 'Item Value',
        'visible'        => true,
        'type'           => 'varchar',
        'apply_to'       => 'simple',
        'frontend_class' => 'validate-number',
        'input'          => 'text',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'frontend_label' => 'Item Value'
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_type_of_product_value');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_type_of_product_value', array(
        'attribute_set'  =>  'Default',
        'group'          => 'ShipHawk Attributes',
        'label'          => 'Origin Contact:',
        'visible'        => true,
        'type'           => 'varchar',
        'apply_to'       => 'simple',
        'input'          => 'text',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'frontend_label' => 'Origin Contact:'
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_firstname');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_firstname', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Origin First Name',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'input'         => 'text',
        'system'        => false,
        'required'      => false,
        'user_defined'  => 1,
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_lastname');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_lastname', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Origin Last Name',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'input'         => 'text',
        'system'        => false,
        'required'      => false,
        'user_defined'  => 1,
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_addressline1');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_addressline1', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Origin Address',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'input'         => 'text',
        'system'        => false,
        'required'      => false,
        'user_defined'  => 1,
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_addressline2');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_addressline2', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Origin Address 2',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'input'         => 'text',
        'system'        => false,
        'required'      => false,
        'user_defined'  => 1,
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_city');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_city', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Origin City',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'input'         => 'text',
        'system'        => false,
        'required'      => false,
        'user_defined'  => 1,
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_state');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_state', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'State',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'input'         => 'select',
        'system'        => false,
        'required'      => false,
        'user_defined'  => 1,
        'option'        => array('values' => array(
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AS' => 'American Samoa',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'AF' => 'Armed Forces Africa',
            'AA' => 'Armed Forces Americas',
            'AC' => 'Armed Forces Canada',
            'AE' => 'Armed Forces Europe',
            'AM' => 'Armed Forces Middle East',
            'AP' => 'Armed Forces Pacific',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'DC' => 'District of Columbia',
            'FM' => 'Federated States Of Micronesia',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'GU' => 'Guam',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MH' => 'Marshall Islands',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'MP' => 'Northern Mariana Islands',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PW' => 'Palau',
            'PA' => 'Pennsylvania',
            'PR' => 'Puerto Rico',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VI' => 'Virgin Islands',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
        ))
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_zipcode');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_zipcode', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Origin Zipcode',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'input'         => 'text',
        'system'        => false,
        'required'      => false,
        'user_defined'  => 1,
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_phonenum');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_phonenum', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Origin Phone',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'input'         => 'text',
        'system'        => false,
        'required'      => false,
        'user_defined'  => 1,
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_location');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_location', array(
        'attribute_set'  =>  'Default',
        'group'          => 'ShipHawk Attributes',
        'label'          => 'Origin Location',
        'visible'        => true,
        'type'           => 'varchar',
        'apply_to'       => 'simple',
        'input'          => 'select',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'option' => array('value' => array(
            'commercial' => array('commercial'),
            'residential' => array('residential')
        )),
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_origin_email');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_origin_email', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Origin Email',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'input'         => 'text',
        'system'        => false,
        'required'      => false,
        'user_defined'  => 1,
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_freight_class');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_freight_class', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Freight Class',
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
        'frontend_label' => 'Freight Class'
    ));
}

$attr = Mage::getResourceModel('catalog/eav_attribute')->loadByCode('catalog_product', 'shiphawk_carrier_type');
if (!$attr->getId()) {
    $installer->addAttribute('catalog_product', 'shiphawk_carrier_type', array(
        'attribute_set' =>  'Default',
        'group'         => 'ShipHawk Attributes',
        'label'         => 'Carrier Type',
        'visible'       => true,
        'type'          => 'varchar',
        'apply_to'      => 'simple',
        'option'        => array('values' => array(
            ''               => 'All',
            'ltl'            => 'ltl',
            'blanket wrap'   => 'blanket wrap',
            'small parcel'   => 'small parcel',
            'vehicle'        => 'vehicle',
            'intermodal'     => 'intermodal',
            'local delivery' => 'local delivery',
        )),
        'input'          => 'multiselect',
        'system'         => false,
        'required'       => false,
        'user_defined'   => 1,
        'frontend_label' => 'Carrier Type'
    ));
}

$installer->endSetup();
