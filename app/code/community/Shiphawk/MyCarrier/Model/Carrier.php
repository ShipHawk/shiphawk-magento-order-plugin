<?php
class ShipHawk_MyCarrier_Model_Carrier
    extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code = 'shiphawk_mycarrier';

    public function collectRates( Mage_Shipping_Model_Rate_Request $request )
    {
        $result = Mage::getModel('shipping/rate_result');
        /* @var $result Mage_Shipping_Model_Rate_Result */

        $groupedItems = $this->getGroupedItems($request);
        // Mage::log('Items: ' . var_export($groupedItems, true), Zend_Log::INFO, 'shiphawk_rates.log', true);

        $rateResponsesArray = array();

        $split_full_street = $arr = explode("\n", $request->getDestStreet());
        $street1 = $split_full_street[0];
        $street2 = $split_full_street[1];

        foreach( $groupedItems as $key => $data) {
            $destination_address = array(
                'country'        => $request->getDestCountryId(),
                'zip'            => $request->getDestPostcode(),
                'city'           => $request->getDestCity(),
                'state'          => $request->getDestRegionCode(),
                'street1'        => $street1,
                'street2'        => $street2,
                'is_residential' => 'true'
            );

            $rateRequest = array(
                'origin_address'      => $data['origin_address'],
                'destination_address' => $destination_address,
                'items'               => $data['items'],
                'apply_rules'         => 'true',
                'display_rate_detail' => 'true',
                'carrier_type_filter' => (count($data['carrier_type_filter']) > 0 ? $data['carrier_type_filter'] : null)
            );

            if (count($groupedItems) > 1){
                $rateRequest['rate_filter'] = 'best';
            }

            $rateResponsesArray[] = array(
                'data'     => $data,
                'response' => $this->getRates($rateRequest)
            );
        }

        $rateArray = new stdClass;
        $rateArray->rates = array();

        if (count($groupedItems) > 1){
            $partialRatesArray = array();
            $combinedRate = $this->_buildCombinedRate();

            foreach ( $rateResponsesArray as $rateResponse ) {
                if($this->_isValidRateResponse($rateResponse)) {
                    $rate = $this->_getRates($rateResponse)->rates[0];
                    $partialRatesArray[] = $rate;

                    // apply markup to the single rate
                    $price = $this->_getShippingPriceWithMarkup($rate, $rateResponse);

                    $combinedRate->price += $price;
                    $combinedRate->id[] = $rate->id;
                    $combinedRate->est_delivery_date = $rate->est_delivery_date;
                }
            }

            if (count($partialRatesArray) == count($groupedItems)){
                $rateArray->rates[] = $combinedRate;
            }
        } else {
            $rateResponse = $rateResponsesArray[0];

            if($this->_isValidRateResponse($rateResponse)) {
                $rateArray = $this->_getRates($rateResponse);

                // apply markup to the rates
                foreach ($rateArray->rates as $rate)
                {
                    $rate->price = $this->_getShippingPriceWithMarkup($rate, $rateResponse);
                }
            }
        }

        // Mage::log($rateArray, Zend_Log::INFO, 'shiphawk_rates.log', true);

        Mage::getSingleton('core/session')->setSHRateAarray($rateArray->rates);

        $freeServices = array();

        if ($request->getFreeShipping() === true) {
            $freeServicesString = $this->getConfigData('free_method');
            if ($freeServicesString) {
                $freeServices = explode(",", $freeServicesString);
            }
        }

        foreach($rateArray->rates as $rateRow)
        {
            $result->append($this->_buildRate($rateRow, $freeServices));
        }

        return $result;
    }

    protected function _buildCombinedRate()
    {
        $combinedRate = new stdClass;
        $combinedRate->id = [];
        $combinedRate->carrier = 'Multiple Carriers';
        $combinedRate->carrier_code = 'multiple_carriers';
        $combinedRate->service_name = 'Multiple Carriers';
        $combinedRate->service_level = 'Multiple Services';
        $combinedRate->standardized_service_name = 'Ground';
        $combinedRate->price = 0.0;
        $combinedRate->currency_code = 'USD';

        return $combinedRate;
    }

    protected function _buildRate($shRate, $freeServices)
    {
        Mage::log('processing rate');
        Mage::log($shRate);
        $rate = Mage::getModel('shipping/rate_result_method');
        /* @var $rate Mage_Shipping_Model_Rate_Result_Method */

        $rate->setCarrier($this->_code);
        /**
         * getConfigData(config_key) returns the configuration value for the
         * carriers/[carrier_code]/[config_key]
         */
        $rate->setCarrierTitle($shRate->carrier);

        $service_title = $shRate->service_name;
        if (substr($shRate->service_name, 0, strlen($shRate->carrier)) !== $shRate->carrier){
            $service_title = $shRate->carrier . ' - ' . $shRate->service_name;
        }

        $rate->setMethod($service_title);
        // $rate->setMethod($shRate->carrier. '-' . $shRate->service_name);

        $rate->setMethodTitle($service_title);

        $rate->setCost($shRate->price);

        if (in_array($shRate->service_name, $freeServices)){
            $rate->setPrice(0);
        } else {
            $rate->setPrice($shRate->price);
        }

        return $rate;
    }

    public function getRates($rateRequest)
    {
        $url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $key = Mage::getStoreConfig('shiphawk/order/api_key');

        $jsonRateRequest = json_encode($rateRequest);

        $client = new Zend_Http_Client($url . 'rates');
        $client->setHeaders('X-Api-Key', $key);

        Mage::log($jsonRateRequest, Zend_Log::INFO, 'shiphawk_rates.log', true);

        $client->setRawData($jsonRateRequest, 'application/json');
        try {
            $response = $client->request(Zend_Http_Client::POST);
            Mage::log('ShipHawk Response: ' . var_export($response, true), Zend_Log::INFO, 'shiphawk_rates.log', true);

            return $response;
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    public function getGroupedItems($request)
    {
        $itemsGrouped = array();
        $skuColumn = Mage::getStoreConfig('shiphawk/datamapping/sku_column');
        Mage::log('getting sku from column: ' . $skuColumn, Zend_Log::INFO, 'shiphawk_rates.log', true);

        $send_items_as_unpacked = Mage::getStoreConfig('carriers/shiphawk_mycarrier/send_items_as_unpacked');

        foreach ($request->getAllItems() as $item) {
            if ($item->getProduct()->isVirtual() || $item->getParentItem()) {
              continue;
            }

            if($option = $item->getOptionByCode('simple_product')) {
                $itemObject = $option;
            } else if( $item->getTypeId() != 'configurable' && !$item->getParentItemId() ){
                $itemObject = $item;
            }

            $product_id = $itemObject->getProductId();
            $product = Mage::getModel('catalog/product')->load($product_id);

            $origin_address = $this->getDefaultOriginAddress();

            if ($product->getData('shiphawk_origin_zipcode')) {
                $origin_address = array(
                    'name'         => join('', array($product->getData('shiphawk_origin_firstname'), $product->getData('shiphawk_origin_lastname'))),
                    'phone_number' => $product->getData('shiphawk_origin_phonenum'),
                    'street1'      => $product->getData('shiphawk_origin_addressline1'),
                    'street2'      => $product->getData('shiphawk_origin_addressline2'),
                    'city'         => $product->getData('shiphawk_origin_city'),
                    'state'        => $this->getSelectAttributeValue($product, 'shiphawk_origin_state'),
                    'country'      => 'US',
                    'zip'          => $product->getData('shiphawk_origin_zipcode'),
                    'email'        => $product->getData('shiphawk_origin_email')
                );
            }

            $carrier_type = $this->getProductCarrierType($product);
            $groupKey = $origin_address['zip'] . ' ' . implode(',', $carrier_type);

            $groupKey .= ', flat_markup=' . $product->getData('shiphawk_discount_fixed');
            $groupKey .= ', percent_markup=' . $product->getData('shiphawk_discount_percentage');

            if (!array_key_exists($groupKey, $itemsGrouped)){
                $itemsGrouped[$groupKey] = array(
                    'origin_address'      => $origin_address,
                    'carrier_type_filter' => $carrier_type,
                    'flat_markup'         => $product->getData('shiphawk_discount_fixed'),
                    'percent_markup'      => $product->getData('shiphawk_discount_percentage'),
                    'items'               => array(),
                    'flat_markup'         => $product->getData('shiphawk_discount_fixed'),
                    'percent_markup'      => $product->getData('shiphawk_discount_percentage')
                );
            }

            $item_weight = $item->getWeight();

            $is_packed = ($product->getData('shiphawk_item_is_packed') == 1);

            if ($send_items_as_unpacked) {
                $item_type = 'unpacked';
            } elseif ($is_packed) {
                $item_type = $item_weight <= 70 ? 'parcel' : 'handling_unit';
            } else {
                $item_type = 'unpacked';
            }

            if ($item_type == 'handling_unit'){
                $handling_unit_type = 'box';
            }

            $shiphawk_quantity = 1;
            if (intval($product->getData('shiphawk_quantity'))>0) {
                $shiphawk_quantity = intval($product->getData('shiphawk_quantity'));
            }

            $value = $product->getData('shiphawk_item_value') ? $product->getData('shiphawk_item_value') : $item->getPrice();
            $quantity = $shiphawk_quantity * $item->getQty();

            $shiphawk_item_weight = $product->getData("shiphawk_item_weight");
            $weight = !empty($shiphawk_item_weight) ? (int)$product->getData("shiphawk_item_weight") : $item_weight;
            $newItem = array(
                'product_sku'           => $product->getData($skuColumn),
                'quantity'              => $quantity,
                'value'                 => $value,
                'length'                => $product->getData('shiphawk_length') ? $product->getData('shiphawk_length') : $itemObject->getLength(),
                'width'                 => $product->getData('shiphawk_width')  ? $product->getData('shiphawk_width')  : $itemObject->getWidth(),
                'height'                => $product->getData('shiphawk_height') ? $product->getData('shiphawk_height') : $itemObject->getHeight(),
                'freight_class'         => $this->getSelectAttributeValue($product, 'shiphawk_freight_class'),
                'weight'                => $weight,
                'weight_uom'            => "lbs",
                'type'                  => $item_type,
                'unpacked_item_type_id' => $product->getData('shiphawk_type_of_product_value'),
                'require_crating'       => $product->getData('shiphawk_item_req_crating') == 1
            );


            if (isset($handling_unit_type)) {
                $newItem['handling_unit_type'] = $handling_unit_type;
            }

            $itemsGrouped[$groupKey]['items'][] = $newItem;

            $itemNumbers = [2,3,4,5,6,7,8,9,10];
            foreach ($itemNumbers as $itemNumber) {
                Mage::log('Iterating item number ' . $itemNumber, Zend_Log::INFO, 'shiphawk_rates.log', true);
                if ((int)$product->getData("shiphawk_item_{$itemNumber}_quantity") < 1){
                    Mage::log('Skiping by quantity < 1 item number ' . $itemNumber, Zend_Log::INFO, 'shiphawk_rates.log', true);
                    continue;
                }


                $weight = (int)$product->getData("shiphawk_item_{$itemNumber}_weight");
                $is_packed = ($product->getData('shiphawk_item_{$itemNumber}_is_packed') == 1);

                if ((int)$product->getData("shiphawk_item_{$itemNumber}_length") < 1 && $weight < 1){
                    Mage::log('Skiping by no length and weight item number ' . $itemNumber, Zend_Log::INFO, 'shiphawk_rates.log', true);
                    continue;
                }

                if ($send_items_as_unpacked) {
                    $item_type = 'unpacked';
                } elseif ($is_packed) {
                    $item_type = $item_weight <= 70 ? 'parcel' : 'handling_unit';
                } else {
                    $item_type = 'unpacked';
                }

                $newItem = array(
                    'quantity'              => $product->getData("shiphawk_item_{$itemNumber}_quantity"),
                    'value'                 => 0,
                    'length'                => $product->getData("shiphawk_item_{$itemNumber}_length"),
                    'width'                 => $product->getData("shiphawk_item_{$itemNumber}_width"),
                    'height'                => $product->getData("shiphawk_item_{$itemNumber}_height"),
                    'freight_class'         => $this->getSelectAttributeValue($product, "shiphawk_item_{$itemNumber}_freight_class"),
                    'weight'                => $weight,
                    'weight_uom'            => "lbs",
                    'type'                  => $item_type,
                    'unpacked_item_type_id' => $product->getData("shiphawk_item_{$itemNumber}_type_id"),
                    'require_crating'       => $product->getData("shiphawk_item_{$itemNumber}_req_crating") == 1
                );

                if (isset($handling_unit_type)) {
                    $newItem['handling_unit_type'] = $handling_unit_type;
                }

                $itemsGrouped[$groupKey]['items'][] = $newItem;
            }
        }

        return $itemsGrouped;

    }

    public function getProductCarrierType($product) {
        $carrier_types =  explode(',', $product->getShiphawkCarrierType());

        $attr = Mage::getModel('catalog/product')->getResource()->getAttribute('shiphawk_carrier_type');
        $carrier_types_labels = array();

        foreach($carrier_types as $carrier_type) {
            if ($attr->usesSource()) {
                $carrier_types_label = $attr->getSource()->getOptionText($carrier_type);

                if(($carrier_types_label == 'All')||(!$carrier_types_label)) {
                    return array();
                }

                $carrier_types_labels[] = $carrier_types_label;
            }
        }

        return $carrier_types_labels;

    }

    protected function getSelectAttributeValue($product, $attributeCode)
    {
        $label = $product->getResource()->getAttribute($attributeCode)->getFrontend()->getValue($product);
        $productModel = Mage::getModel('catalog/product');
        $attr = $productModel->getResource()->getAttribute($attributeCode);

        if ($attr->usesSource()) {
            return $attr->getSource()->getOptionId($label);
        }
    }

    protected function getDefaultOriginAddress()
    {
        return array(
            'name'         => Mage::getStoreConfig('general/store_information/name'),
            'phone_number' => Mage::getStoreConfig('general/store_information/phone'),
            'street1'      => Mage::getStoreConfig('shipping/origin/street_line1'),
            'street2'      => Mage::getStoreConfig('shipping/origin/street_line2'),
            'city'         => Mage::getStoreConfig('shipping/origin/city'),
            'state'        => Mage::getModel('directory/region')
                                ->load(Mage::getStoreConfig('shipping/origin/region_id'))
                                ->getCode(),
            'country'      => Mage::getStoreConfig('shipping/origin/country_id'),
            'zip'          => Mage::getStoreConfig('shipping/origin/postcode'),
        );
    }

    protected function shippingPriceWithMarkup($shPrice, $flatMarkup, $percentMarkup)
    {
        $price = $shPrice;

        if ($flatMarkup) {
            $price += floatval($flatMarkup);
        } elseif ($percentMarkup) {
            $price += $price * floatval($percentMarkup) / 100;
        }

        if ($price < 0) {
            return 0;
        } else {
            return $price;
        }
    }

    protected function getTotalPriceFromDetailedResponse($shRate)
    {
        return floatval($shRate->price);
    }

    public function getAllowedMethods()
    {
        return array(
          'AirSea Packing - White Glove'                                             => 'AirSea Packing - White Glove',
          'American West - White Glove'                                              => 'American West - White Glove',
          'American West - White Glove'                                              => 'American West - White Glove',
          'B Cubed - White Glove'                                                    => 'B Cubed - White Glove',
          'Justo - White Glove'                                                      => 'Justo - White Glove',
          'Metropolitan Delivery BW - White Glove'                                   => 'Metropolitan Delivery BW - White Glove',
          'Plycon - White Glove'                                                     => 'Plycon - White Glove',
          'Rancho Buck - White Glove'                                                => 'Rancho Buck - White Glove',
          'RickShips - White Glove'                                                  => 'RickShips - White Glove',
          'Sterling Van Lines - White Glove'                                         => 'Sterling Van Lines - White Glove',
          'Ukay - White Glove'                                                       => 'Ukay - White Glove',
          'UKAY DO NOT USE - White Glove'                                            => 'UKAY DO NOT USE - White Glove',
          'Vintage Transport - White Glove'                                          => 'Vintage Transport - White Glove',
          '1-800-Courier - Standard Courier Service'                                 => '1-800-Courier - Standard Courier Service',
          'Lodeso - Premium'                                                         => 'Lodeso - Premium',
          'Lodeso - Threshold'                                                       => 'Lodeso - Threshold',
          'MXD Final Mile - Deluxed Delivery'                                        => 'MXD Final Mile - Deluxed Delivery',
          'MXD Final Mile - Over the Threshold'                                      => 'MXD Final Mile - Over the Threshold',
          'MXD Final Mile - Room of Choice'                                          => 'MXD Final Mile - Room of Choice',
          'MXD Final Mile - White Glove'                                             => 'MXD Final Mile - White Glove',
          'Flatbed'                                                                  => 'Flatbed',
          'Metropolitan - White Glove Delivery'                                      => 'Metropolitan - White Glove Delivery',
          'MXD - Deluxed Delivery'                                                   => 'MXD - Deluxed Delivery',
          'MXD - Over the Threshold'                                                 => 'MXD - Over the Threshold',
          'MXD - Room of Choice'                                                     => 'MXD - Room of Choice',
          'MXD - White Glove'                                                        => 'MXD - White Glove',
          'NonstopDelivery - White Glove Service - White Glove'                      => 'NonstopDelivery - White Glove Service - White Glove',
          'Pilot - Deluxe'                                                           => 'Pilot - Deluxe',
          'Pilot - Premier'                                                          => 'Pilot - Premier',
          'Pilot - Standard One Man'                                                 => 'Pilot - Standard One Man',
          'Pilot - Standard Two Man'                                                 => 'Pilot - Standard Two Man',
          'STI - Basic Service Delivery'                                             => 'STI - Basic Service Delivery',
          'STI - Premier Service Delivery'                                           => 'STI - Premier Service Delivery',
          'Team World Wide Hybrid - White Glove'                                     => 'Team World Wide Hybrid - White Glove',
          'Watkins and Shepard - Threshold'                                          => 'Watkins and Shepard - Threshold',
          'XPO Last Mile - Room of Choice'                                           => 'XPO Last Mile - Room of Choice',
          'XPO Last Mile - Threshold'                                                => 'XPO Last Mile - Threshold',
          'XPO Last Mile - White Glove'                                              => 'XPO Last Mile - White Glove',
          'Local Delivery'                                                           => 'Local Delivery',
          'Paddle8 Local Delivery - Local Delivery'                                  => 'Paddle8 Local Delivery - Local Delivery',
          'A & B FREIGHT LINE INC - Standard Freight Service'                        => 'A & B FREIGHT LINE INC - Standard Freight Service',
          'A-1 EXPRESS DELIVERY SERVICE, INC. - Standard Freight Service'            => 'A-1 EXPRESS DELIVERY SERVICE, INC. - Standard Freight Service',
          'AAA Cooper - Standard Freight Service'                                    => 'AAA Cooper - Standard Freight Service',
          'ABC Transfer and Delivery - Standard Freight Service'                     => 'ABC Transfer and Delivery - Standard Freight Service',
          'ABERDEEN EXPRESS INC - Standard Freight Service'                          => 'ABERDEEN EXPRESS INC - Standard Freight Service',
          'USPS - Priority Mail Flat Rate Envelope'                                  => 'USPS - Priority Mail Flat Rate Envelope',
          'ABQ Express Cartage Inc. - Standard Freight Service'                      => 'ABQ Express Cartage Inc. - Standard Freight Service',
          'Accelerated Courier - Standard Freight Service'                           => 'Accelerated Courier - Standard Freight Service',
          'ACT - Standard Freight Service'                                           => 'ACT - Standard Freight Service',
          'Alaska Traffic Company - Standard Freight Service'                        => 'Alaska Traffic Company - Standard Freight Service',
          'ALOHA FREIGHT FORWARDERS - Standard Freight Service'                      => 'ALOHA FREIGHT FORWARDERS - Standard Freight Service',
          'AMA Transportation Co - Standard Freight Service'                         => 'AMA Transportation Co - Standard Freight Service',
          'APEX XPRESS - Standard Freight Service'                                   => 'APEX XPRESS - Standard Freight Service',
          'Approved Freight Forwarders - Standard Freight Service'                   => 'Approved Freight Forwarders - Standard Freight Service',
          'Armbro Transport, Inc (USD) - Standard Freight Service'                   => 'Armbro Transport, Inc (USD) - Standard Freight Service',
          'ATCHESON\'S EXPRESS, INC. - Standard Freight Service'                     => 'ATCHESON\'S EXPRESS, INC. - Standard Freight Service',
          'Atlas Motor Express - Standard Freight Service'                           => 'Atlas Motor Express - Standard Freight Service',
          'AIT - 4 Day - Domestic Heavyweight 150+'                                  => 'AIT - 4 Day - Domestic Heavyweight 150+',
          'Averitt Express - Standard Freight Service'                               => 'Averitt Express - Standard Freight Service',
          'B&H Freight Line, Inc - Standard Freight Service'                         => 'B&H Freight Line, Inc - Standard Freight Service',
          'Beaver Express Service LLC - Standard Freight Service'                    => 'Beaver Express Service LLC - Standard Freight Service',
          'BENJAMIN BEST FREIGHT INC - Standard Freight Service'                     => 'BENJAMIN BEST FREIGHT INC - Standard Freight Service',
          'Benton Express Inc - Standard Freight Service'                            => 'Benton Express Inc - Standard Freight Service',
          'Best Overnite Express, Inc. - Standard Freight Service'                   => 'Best Overnite Express, Inc. - Standard Freight Service',
          'Best Yet Express - Standard Freight Service'                              => 'Best Yet Express - Standard Freight Service',
          'Buffalo Transport Co., Inc - Standard Freight Service'                    => 'Buffalo Transport Co., Inc - Standard Freight Service',
          'BULLET TRANSPORTATION SERVICES, INC. - Standard Freight Service'          => 'BULLET TRANSPORTATION SERVICES, INC. - Standard Freight Service',
          'CAL STATE XPRESS - Standard Freight Service'                              => 'CAL STATE XPRESS - Standard Freight Service',
          'CAPE COD EXPRESS, INC. - Standard Freight Service'                        => 'CAPE COD EXPRESS, INC. - Standard Freight Service',
          'Capitol Express, Inc. - Standard Freight Service'                         => 'Capitol Express, Inc. - Standard Freight Service',
          'Pilot - Basic'                                                            => 'Pilot - Basic',
          'Cargomatic - Same-Day Freight Service'                                    => 'Cargomatic - Same-Day Freight Service',
          'Cathay Logistics LLC - Standard Freight Service'                          => 'Cathay Logistics LLC - Standard Freight Service',
          'Central Freight Lines, Inc. - Standard Freight Service'                   => 'Central Freight Lines, Inc. - Standard Freight Service',
          'Central Transport - Standard Freight Service'                             => 'Central Transport - Standard Freight Service',
          'Central_Courier - Standard Freight Service'                               => 'Central_Courier - Standard Freight Service',
          'Central_Transport - Standard Freight Service'                             => 'Central_Transport - Standard Freight Service',
          'Ceva Logistics (Elkay Use Only) - Standard Freight Service'               => 'Ceva Logistics (Elkay Use Only) - Standard Freight Service',
          'Chapin Logistic Solutions - Standard Freight Service'                     => 'Chapin Logistic Solutions - Standard Freight Service',
          'Chicago Suburban Express, Inc. - Standard Freight Service'                => 'Chicago Suburban Express, Inc. - Standard Freight Service',
          'CLEAR LANE FREIGHT SYSTEMS, LLC - Standard Freight Service'               => 'CLEAR LANE FREIGHT SYSTEMS, LLC - Standard Freight Service',
          'Clearlane - Standard Freight Service'                                     => 'Clearlane - Standard Freight Service',
          'Con-Way - Guaranteed Delivery by 5PM'                                     => 'Con-Way - Guaranteed Delivery by 5PM',
          'Con-Way - Guaranteed Freight Service'                                     => 'Con-Way - Guaranteed Freight Service',
          'Con-Way - Standard Freight Service'                                       => 'Con-Way - Standard Freight Service',
          'Concord Transportation, Inc. - Standard Freight Service'                  => 'Concord Transportation, Inc. - Standard Freight Service',
          'Contact Cartage, Inc. - Standard Freight Service'                         => 'Contact Cartage, Inc. - Standard Freight Service',
          'Courier Express Freight Inc. - Standard Freight Service'                  => 'Courier Express Freight Inc. - Standard Freight Service',
          'CROSSCOUNTRY COURIER INC - Standard Freight Service'                      => 'CROSSCOUNTRY COURIER INC - Standard Freight Service',
          'CSA TRANSPORTATION - Standard Freight Service'                            => 'CSA TRANSPORTATION - Standard Freight Service',
          'D&A Truck Lines Inc - Standard Freight Service'                           => 'D&A Truck Lines Inc - Standard Freight Service',
          'DATS Trucking - Standard Freight Service'                                 => 'DATS Trucking - Standard Freight Service',
          'Day and Ross - Standard Freight Service'                                  => 'Day and Ross - Standard Freight Service',
          'Day and Ross (Archway NEM)(CAD) - Standard Freight Service'               => 'Day and Ross (Archway NEM)(CAD) - Standard Freight Service',
          'Dayton Freight Lines, Inc. - Standard Freight Service'                    => 'Dayton Freight Lines, Inc. - Standard Freight Service',
          'Dc Logistics - Standard Freight Service'                                  => 'Dc Logistics - Standard Freight Service',
          'DEDICATED DELIVERY PROFESSIONALS INC - Standard Freight Service'          => 'DEDICATED DELIVERY PROFESSIONALS INC - Standard Freight Service',
          'DENVER EXPRESS LLC - Standard Freight Service'                            => 'DENVER EXPRESS LLC - Standard Freight Service',
          'Diamond Line Delivery Systems Inc - Standard Freight Service'             => 'Diamond Line Delivery Systems Inc - Standard Freight Service',
          'DOHRN Transfer Co. - Standard Freight Service'                            => 'DOHRN Transfer Co. - Standard Freight Service',
          'Dohrn Transfer Company - Standard Freight Service'                        => 'Dohrn Transfer Company - Standard Freight Service',
          'DOT-Line Transportation - Standard Freight Service'                       => 'DOT-Line Transportation - Standard Freight Service',
          'DS Cargo Inc - Standard Freight Service'                                  => 'DS Cargo Inc - Standard Freight Service',
          'Dugan Truck Line - Standard Freight Service'                              => 'Dugan Truck Line - Standard Freight Service',
          'Dura Freight Lines - Standard Freight Service'                            => 'Dura Freight Lines - Standard Freight Service',
          'Dusk2Dawn - Standard Freight Service'                                     => 'Dusk2Dawn - Standard Freight Service',
          'DYNAMEX OPERATIONS EAST, INC. - Standard Freight Service'                 => 'DYNAMEX OPERATIONS EAST, INC. - Standard Freight Service',
          'East/West Consolidators - Standard Freight Service'                       => 'East/West Consolidators - Standard Freight Service',
          'EDI Express - Standard Freight Service'                                   => 'EDI Express - Standard Freight Service',
          'Edina Couriers, LLC - Standard Freight Service'                           => 'Edina Couriers, LLC - Standard Freight Service',
          'USPS - Priority Mail Express Flat Rate Envelope'                          => 'USPS - Priority Mail Express Flat Rate Envelope',
          'USPS - Priority Mail Flat Rate Legal Envelope'                            => 'USPS - Priority Mail Flat Rate Legal Envelope',
          'Estes Level 2 Logistics - Standard Freight Service'                       => 'Estes Level 2 Logistics - Standard Freight Service',
          'EXPRESS 2000, INC. - Standard Freight Service'                            => 'EXPRESS 2000, INC. - Standard Freight Service',
          'FedEx Economy - Guaranteed Delivery by 5PM'                               => 'FedEx Economy - Guaranteed Delivery by 5PM',
          'FedEx Economy - Standard Freight Service'                                 => 'FedEx Economy - Standard Freight Service',
          'FedEx Express Freight - FedEx 1 Day Freight'                              => 'FedEx Express Freight - FedEx 1 Day Freight',
          'FedEx Express Freight - FedEx 1 Day Freight Saturday Delivery'            => 'FedEx Express Freight - FedEx 1 Day Freight Saturday Delivery',
          'FedEx Express Freight - FedEx 2 Day Freight'                              => 'FedEx Express Freight - FedEx 2 Day Freight',
          'FedEx Express Freight - FedEx 3 Day Freight'                              => 'FedEx Express Freight - FedEx 3 Day Freight',
          'USPS - Priority Mail Express Flat Rate Legal Envelope'                    => 'USPS - Priority Mail Express Flat Rate Legal Envelope',
          'FIRST CAPITOL COURIER, INC. - Standard Freight Service'                   => 'FIRST CAPITOL COURIER, INC. - Standard Freight Service',
          'FORT TRANSPORTATION AND SERVICE COMPANY, INC. - Standard Freight Service' => 'FORT TRANSPORTATION AND SERVICE COMPANY, INC. - Standard Freight Service',
          'FORWARD AIR, INC - Standard Freight Service'                              => 'FORWARD AIR, INC - Standard Freight Service',
          'FREIGHT DIRECTION, INC. - Standard Freight Service'                       => 'FREIGHT DIRECTION, INC. - Standard Freight Service',
          'Freight Force, Inc - Standard Freight Service'                            => 'Freight Force, Inc - Standard Freight Service',
          'Frontline - Standard Freight Service'                                     => 'Frontline - Standard Freight Service',
          'FRONTLINE CARRIER SYSTEMS (USA) INC. - Standard Freight Service'          => 'FRONTLINE CARRIER SYSTEMS (USA) INC. - Standard Freight Service',
          'GENCOM TRANSPORTATION INC - Standard Freight Service'                     => 'GENCOM TRANSPORTATION INC - Standard Freight Service',
          'Gio Express Inc - Standard Freight Service'                               => 'Gio Express Inc - Standard Freight Service',
          'GOLD COAST FREIGHTWAYS, INC. - Standard Freight Service'                  => 'GOLD COAST FREIGHTWAYS, INC. - Standard Freight Service',
          'Grane Transportation Lines, LTD - Standard Freight Service'               => 'Grane Transportation Lines, LTD - Standard Freight Service',
          'Greyhound - Standard Freight Service'                                     => 'Greyhound - Standard Freight Service',
          'Holland - Standard Freight Service'                                       => 'Holland - Standard Freight Service',
          'J & S Airfreight, Inc - Standard Freight Service'                         => 'J & S Airfreight, Inc - Standard Freight Service',
          'Jack Jones Trucking, Inc. - Standard Freight Service'                     => 'Jack Jones Trucking, Inc. - Standard Freight Service',
          'Jeff\'s Fast Freight, Inc. - Standard Freight Service'                    => 'Jeff\'s Fast Freight, Inc. - Standard Freight Service',
          'John Dotseth Trucking, Inc. - Standard Freight Service'                   => 'John Dotseth Trucking, Inc. - Standard Freight Service',
          'Kindersley Transport, Ltd.(CAD) - Standard Freight Service'               => 'Kindersley Transport, Ltd.(CAD) - Standard Freight Service',
          'Kingsway Logistics, Inc. - Standard Freight Service'                      => 'Kingsway Logistics, Inc. - Standard Freight Service',
          'Kingsway Transport (Canadian Currency) - Standard Freight Service'        => 'Kingsway Transport (Canadian Currency) - Standard Freight Service',
          'Lakeville - Standard Freight Service'                                     => 'Lakeville - Standard Freight Service',
          'Land Air Express - Standard Freight Service'                              => 'Land Air Express - Standard Freight Service',
          'Lanter Refrigerated Distributing, Co. - Standard Freight Service'         => 'Lanter Refrigerated Distributing, Co. - Standard Freight Service',
          'Mach 1 Air Services, Inc - Standard Freight Service'                      => 'Mach 1 Air Services, Inc - Standard Freight Service',
          'Magnum Logistics - Standard Freight Service'                              => 'Magnum Logistics - Standard Freight Service',
          'Mayfield Transfer - Standard Freight Service'                             => 'Mayfield Transfer - Standard Freight Service',
          'MERGENTHALER TRANSFER AND STOR - Standard Freight Service'                => 'MERGENTHALER TRANSFER AND STOR - Standard Freight Service',
          'MICHAEL TOMLINSON TRUCKING, LLC - Standard Freight Service'               => 'MICHAEL TOMLINSON TRUCKING, LLC - Standard Freight Service',
          'Midwest Motor Express, Inc. - Standard Freight Service'                   => 'Midwest Motor Express, Inc. - Standard Freight Service',
          'Milan Express, Inc. - Standard Freight Service'                           => 'Milan Express, Inc. - Standard Freight Service',
          'Monroe Transportation Service, Inc. - Standard Freight Service'           => 'Monroe Transportation Service, Inc. - Standard Freight Service',
          'Mountain Valley Express Co., Inc. - Standard Freight Service'             => 'Mountain Valley Express Co., Inc. - Standard Freight Service',
          'N&M Transfer Co., Inc. - Standard Freight Service'                        => 'N&M Transfer Co., Inc. - Standard Freight Service',
          'NEBRASKA TRANSPORT CO. INC - Standard Freight Service'                    => 'NEBRASKA TRANSPORT CO. INC - Standard Freight Service',
          'New England Motor Freight, Inc. - Standard Freight Service'               => 'New England Motor Freight, Inc. - Standard Freight Service',
          'New Penn Motor Express, Inc. - Standard Freight Service'                  => 'New Penn Motor Express, Inc. - Standard Freight Service',
          'North Park Transportation - Standard Freight Service'                     => 'North Park Transportation - Standard Freight Service',
          'Northern Refrigerated Transportation - Standard Freight Service'          => 'Northern Refrigerated Transportation - Standard Freight Service',
          'Northland Trucking - Standard Freight Service'                            => 'Northland Trucking - Standard Freight Service',
          'Northwest Furniture Express - Standard Freight Service'                   => 'Northwest Furniture Express - Standard Freight Service',
          'USPS - Priority Mail Small Flat Rate Box'                                 => 'USPS - Priority Mail Small Flat Rate Box',
          'USPS - Priority Mail Medium Flat Rate Box'                                => 'USPS - Priority Mail Medium Flat Rate Box',
          'Pace Motor Lines, Inc. - Standard Freight Service'                        => 'Pace Motor Lines, Inc. - Standard Freight Service',
          'Pacific Alaska Freightways - Standard Freight Service'                    => 'Pacific Alaska Freightways - Standard Freight Service',
          'PANAMA TRANSFER INC. - Standard Freight Service'                          => 'PANAMA TRANSFER INC. - Standard Freight Service',
          'USPS - Priority Mail Large Flat Rate Box'                                 => 'USPS - Priority Mail Large Flat Rate Box',
          'Performance Freight System, Inc. - Standard Freight Service'              => 'Performance Freight System, Inc. - Standard Freight Service',
          'Pitt Ohio Express, Inc. - Standard Freight Service'                       => 'Pitt Ohio Express, Inc. - Standard Freight Service',
          'Post & Pallet LLC - Standard Freight Service'                             => 'Post & Pallet LLC - Standard Freight Service',
          'PRICE TRUCK LINE - Standard Freight Service'                              => 'PRICE TRUCK LINE - Standard Freight Service',
          'PRIORITY COURIER EXPERTS/VANEX - Standard Freight Service'                => 'PRIORITY COURIER EXPERTS/VANEX - Standard Freight Service',
          'ProTrans International - Standard Freight Service'                        => 'ProTrans International - Standard Freight Service',
          'Pyle Transport Services - Standard Freight Service'                       => 'Pyle Transport Services - Standard Freight Service',
          'R and L Carriers - Guaranteed Delivery by 5PM'                            => 'R and L Carriers - Guaranteed Delivery by 5PM',
          'R and L Carriers - Standard Freight Service'                              => 'R and L Carriers - Standard Freight Service',
          'USPS - Priority Mail International Flat Rate Envelope'                    => 'USPS - Priority Mail International Flat Rate Envelope',
          'USPS - Priority Mail Express International Flat Rate Envelope'            => 'USPS - Priority Mail Express International Flat Rate Envelope',
          'USPS - Priority Mail International Flat Rate Legal Envelope'              => 'USPS - Priority Mail International Flat Rate Legal Envelope',
          'USPS - Priority Mail Express International Flat Rate Legal Envelope'      => 'USPS - Priority Mail Express International Flat Rate Legal Envelope',
          'USPS - Priority Mail International Flat Rate Padded Envelope'             => 'USPS - Priority Mail International Flat Rate Padded Envelope',
          'Reserve Truck Lines, Inc. - Standard Freight Service'                     => 'Reserve Truck Lines, Inc. - Standard Freight Service',
          'Riegel Transportation, Inc - Standard Freight Service'                    => 'Riegel Transportation, Inc - Standard Freight Service',
          'RIST TRANSPORT, LTD. - Standard Freight Service'                          => 'RIST TRANSPORT, LTD. - Standard Freight Service',
          'RJW TRANSPORT, INC. - Standard Freight Service'                           => 'RJW TRANSPORT, INC. - Standard Freight Service',
          'Roadrunner Dawes Freight Systems Inc. - Standard Freight Service'         => 'Roadrunner Dawes Freight Systems Inc. - Standard Freight Service',
          'Roadrunner Dawes Freight Systems Inc. - Standard Freight Service'         => 'Roadrunner Dawes Freight Systems Inc. - Standard Freight Service',
          'Roadstar Trucking, Inc - Standard Freight Service'                        => 'Roadstar Trucking, Inc - Standard Freight Service',
          'Roseville Motor Express - Standard Freight Service'                       => 'Roseville Motor Express - Standard Freight Service',
          'Ross Express, Inc. - Standard Freight Service'                            => 'Ross Express, Inc. - Standard Freight Service',
          'RPM Transportation - Standard Freight Service'                            => 'RPM Transportation - Standard Freight Service',
          'Rude Transportation Co., Inc. - Standard Freight Service'                 => 'Rude Transportation Co., Inc. - Standard Freight Service',
          'USPS - Priority Mail Express International Flat Rate Padded Envelope'     => 'USPS - Priority Mail Express International Flat Rate Padded Envelope',
          'Shift Freight - Standard Freight Service'                                 => 'Shift Freight - Standard Freight Service',
          'Southwestern Motor Transport Inc - Standard Freight Service'              => 'Southwestern Motor Transport Inc - Standard Freight Service',
          'Standard Forwarding Company, Inc. - Standard Freight Service'             => 'Standard Forwarding Company, Inc. - Standard Freight Service',
          'Sun Delivery - Standard Freight Service'                                  => 'Sun Delivery - Standard Freight Service',
          'SuperVan Service - Standard Freight Service'                              => 'SuperVan Service - Standard Freight Service',
          'Sutton Transport - Standard Freight Service'                              => 'Sutton Transport - Standard Freight Service',
          'Tax Airfreight - Standard Freight Service'                                => 'Tax Airfreight - Standard Freight Service',
          'TCBX Inc - Standard Freight Service'                                      => 'TCBX Inc - Standard Freight Service',
          'Teals Express, Inc. - Standard Freight Service'                           => 'Teals Express, Inc. - Standard Freight Service',
          'The Custom Companies, Inc. - Standard Freight Service'                    => 'The Custom Companies, Inc. - Standard Freight Service',
          'The Expediting Co - Standard Freight Service'                             => 'The Expediting Co - Standard Freight Service',
          'Titan Freight Systems - Standard Freight Service'                         => 'Titan Freight Systems - Standard Freight Service',
          'TOTAL TRANSPORTATION & DISTRIBUTION INC - Standard Freight Service'       => 'TOTAL TRANSPORTATION & DISTRIBUTION INC - Standard Freight Service',
          'Towne Air Freight - Standard Freight Service'                             => 'Towne Air Freight - Standard Freight Service',
          'TP Freight Lines Inc - Standard Freight Service'                          => 'TP Freight Lines Inc - Standard Freight Service',
          'Transport TFI 2. SEC (CAD) - Standard Freight Service'                    => 'Transport TFI 2. SEC (CAD) - Standard Freight Service',
          'TsMotorFreight - Standard Freight Service'                                => 'TsMotorFreight - Standard Freight Service',
          'TST Overland Express (Canadian Currency) - Standard Freight Service'      => 'TST Overland Express (Canadian Currency) - Standard Freight Service',
          'TsVanLine - Standard Freight Service'                                     => 'TsVanLine - Standard Freight Service',
          'USPS - Priority Mail International Small Flat Rate Box'                   => 'USPS - Priority Mail International Small Flat Rate Box',
          'USPS - Priority Mail'                                                     => 'USPS - Priority Mail',
          'US Road Freight Express, Inc - Standard Freight Service'                  => 'US Road Freight Express, Inc - Standard Freight Service',
          'US Special Delivery - Standard Freight Service'                           => 'US Special Delivery - Standard Freight Service',
          'USA OPPORTUNITY LLC - Standard Freight Service'                           => 'USA OPPORTUNITY LLC - Standard Freight Service',
          'Valley Cartage - Standard Freight Service'                                => 'Valley Cartage - Standard Freight Service',
          'VECTORS LOGISTIC INC - Standard Freight Service'                          => 'VECTORS LOGISTIC INC - Standard Freight Service',
          'Vitran Express - Standard Freight Service'                                => 'Vitran Express - Standard Freight Service',
          'Volunteer Express, Inc. - Standard Freight Service'                       => 'Volunteer Express, Inc. - Standard Freight Service',
          'Ward Trucking, LLC - Standard Freight Service'                            => 'Ward Trucking, LLC - Standard Freight Service',
          'WEL Companies Inc - Standard Freight Service'                             => 'WEL Companies Inc - Standard Freight Service',
          'West Bend Transit & Service Co - Standard Freight Service'                => 'West Bend Transit & Service Co - Standard Freight Service',
          'Wilson Trucking Corporation - Standard Freight Service'                   => 'Wilson Trucking Corporation - Standard Freight Service',
          'XPRESS GLOBAL SYSTEMS, INC. - Standard Freight Service'                   => 'XPRESS GLOBAL SYSTEMS, INC. - Standard Freight Service',
          'USPS - Priority Mail International Medium Flat Rate Box'                  => 'USPS - Priority Mail International Medium Flat Rate Box',
          'USPS - Priority Mail International Large Flat Rate Box'                   => 'USPS - Priority Mail International Large Flat Rate Box',
          'AIT - 2 Day AM (before noon) - Domestic Heavyweight 150+'                 => 'AIT - 2 Day AM (before noon) - Domestic Heavyweight 150+',
          'DHL - DOMESTIC EXPRESS'                                                   => 'DHL - DOMESTIC EXPRESS',
          'DHL - ECONOMY SELECT'                                                     => 'DHL - ECONOMY SELECT',
          'DHL - EXPRESS 10:30'                                                      => 'DHL - EXPRESS 10:30',
          'DHL - EXPRESS 12:00'                                                      => 'DHL - EXPRESS 12:00',
          'DHL - EXPRESS 9:00'                                                       => 'DHL - EXPRESS 9:00',
          'DHL - EXPRESS EASY'                                                       => 'DHL - EXPRESS EASY',
          'DHL - EXPRESS WORLDWIDE'                                                  => 'DHL - EXPRESS WORLDWIDE',
          'DHL - FREIGHT WORLDWIDE'                                                  => 'DHL - FREIGHT WORLDWIDE',
          'DHL - JETLINE'                                                            => 'DHL - JETLINE',
          'FedEx 2 Day'                                                              => 'FedEx 2 Day',
          'FedEx 2 Day Am'                                                           => 'FedEx 2 Day Am',
          'FedEx Express Saver'                                                      => 'FedEx Express Saver',
          'FedEx First Overnight'                                                    => 'FedEx First Overnight',
          'FedEx First Overnight Saturday Delivery'                                  => 'FedEx First Overnight Saturday Delivery',
          'FedEx Ground Home Delivery'                                               => 'FedEx Ground Home Delivery',
          'FedEx International Economy'                                              => 'FedEx International Economy',
          'FedEx International First'                                                => 'FedEx International First',
          'FedEx International Priority'                                             => 'FedEx International Priority',
          'FedEx Priority Overnight'                                                 => 'FedEx Priority Overnight',
          'FedEx Priority Overnight Saturday Delivery'                               => 'FedEx Priority Overnight Saturday Delivery',
          'FedEx Standard Overnight'                                                 => 'FedEx Standard Overnight',
          'AIT - 2 Day - Domestic Heavyweight 150+'                                  => 'AIT - 2 Day - Domestic Heavyweight 150+',
          'USPS - First-Class Mail'                                                  => 'USPS - First-Class Mail',
          'USPS - Library Mail'                                                      => 'USPS - Library Mail',
          'USPS - Media Mail'                                                        => 'USPS - Media Mail',
          'USPS - Parcel Select Ground'                                              => 'USPS - Parcel Select Ground',
          'USPS - Priority Mail Express'                                             => 'USPS - Priority Mail Express',
          'Montway - Standard Vehicle Service'                                       => 'Montway - Standard Vehicle Service',
          'Plycar - Standard Vehicle Service'                                        => 'Plycar - Standard Vehicle Service',
          'OnTrac - Sunrise'                                                         => 'OnTrac - Sunrise',
          'OnTrac - Sunrise Gold'                                                    => 'OnTrac - Sunrise Gold',
          'OnTrac - Palletized Freight'                                              => 'OnTrac - Palletized Freight',
          'OnTrac - Ground'                                                          => 'OnTrac - Ground',
          'OnTrac - Same Day'                                                        => 'OnTrac - Same Day',
          'FedEx Freight Economy'                                                    => 'FedEx Freight Economy',
          'FedEx Freight Priority'                                                   => 'FedEx Freight Priority',
          'FedEx Ground'                                                             => 'FedEx Ground',
          'UPS Second Day Air'                                                       => 'UPS Second Day Air',
          'UPS Three-Day Select'                                                     => 'UPS Three-Day Select',
          'UPS Second Day Air A.M.'                                                  => 'UPS Second Day Air A.M.',
          'UPS Next Day Air Early'                                                   => 'UPS Next Day Air Early',
          'SEFL 550 - Standard Freight Service'                                      => 'SEFL 550 - Standard Freight Service',
          'Old Dominion Freight Line, Inc. - Standard Freight Service'               => 'Old Dominion Freight Line, Inc. - Standard Freight Service',
          'Old Dominion Freight Line, Inc. - Guaranteed Freight Service'             => 'Old Dominion Freight Line, Inc. - Guaranteed Freight Service',
          'ABF Freight Systems - Standard Freight Service'                           => 'ABF Freight Systems - Standard Freight Service',
          'Dependable Highway Express - Standard Freight Service'                    => 'Dependable Highway Express - Standard Freight Service',
          'Oak Harbor Freight Lines - Standard Freight Service'                      => 'Oak Harbor Freight Lines - Standard Freight Service',
          'Peninsula Truck Lines - Standard Freight Service'                         => 'Peninsula Truck Lines - Standard Freight Service',
          'ATS - Flatbed'                                                            => 'ATS - Flatbed',
          'ATS - Stepdeck'                                                           => 'ATS - Stepdeck',
          'ATS - RGN'                                                                => 'ATS - RGN',
          'Daylight - Standard Freight Service'                                      => 'Daylight - Standard Freight Service',
          'Estes - Standard Freight Service'                                         => 'Estes - Standard Freight Service',
          'Reddaway - Standard Freight Service'                                      => 'Reddaway - Standard Freight Service',
          'UPS Freight - Standard Freight Service'                                   => 'UPS Freight - Standard Freight Service',
          'Fedex Freight Canada Corp. (CAD) - Standard Freight Service'              => 'Fedex Freight Canada Corp. (CAD) - Standard Freight Service',
          'YRC - Standard Freight Service'                                           => 'YRC - Standard Freight Service',
          'YRC - Guaranteed Freight Service'                                         => 'YRC - Guaranteed Freight Service',
          'Saia Motor Freight Line - Standard Freight Service'                       => 'Saia Motor Freight Line - Standard Freight Service',
          'RandL - Standard Freight Service'                                         => 'RandL - Standard Freight Service',
          'RandL - Guaranteed Freight Service'                                       => 'RandL - Guaranteed Freight Service',
          'Southeastern Freight Lines - Standard Freight Service'                    => 'Southeastern Freight Lines - Standard Freight Service',
          'Team World Wide LTL - Next Day AM Delivery'                               => 'Team World Wide LTL - Next Day AM Delivery',
          'Team World Wide LTL - 3 Day Delivery'                                     => 'Team World Wide LTL - 3 Day Delivery',
          'Team World Wide LTL - 2 Day Delivery'                                     => 'Team World Wide LTL - 2 Day Delivery',
          'Team World Wide LTL - Next Day PM Delivery'                               => 'Team World Wide LTL - Next Day PM Delivery',
          'Team World Wide LTL - Economy Service'                                    => 'Team World Wide LTL - Economy Service',
          'UPS SurePost'                                                             => 'UPS SurePost',
          'UPS Worldwide Express'                                                    => 'UPS Worldwide Express',
          'UPS Worldwide Expedited'                                                  => 'UPS Worldwide Expedited',
          'UPS Worldwide Express Plus'                                               => 'UPS Worldwide Express Plus',
          'UPS Worldwide Saver'                                                      => 'UPS Worldwide Saver',
          'UPS Standard'                                                             => 'UPS Standard',
          'UPS Worldwide Express Freight'                                            => 'UPS Worldwide Express Freight',
          'USPS - Global Express Guaranteed'                                         => 'USPS - Global Express Guaranteed',
          'USPS - Priority Mail Express International'                               => 'USPS - Priority Mail Express International',
          'USPS - Priority Mail International'                                       => 'USPS - Priority Mail International',
          'USPS - First Class Mail International'                                    => 'USPS - First Class Mail International',
          'USPS - First Class Package International Service'                         => 'USPS - First Class Package International Service',
          'UPS Next Day Air'                                                         => 'UPS Next Day Air',
          'UPS Ground'                                                               => 'UPS Ground',
          'UPS Next Day Air Saver'                                                   => 'UPS Next Day Air Saver',
          'FedEx International Ground'                                               => 'FedEx International Ground',
          'AIT - Same Day - Domestic Heavyweight 150+'                               => 'AIT - Same Day - Domestic Heavyweight 150+',
          'AIT - Next Flight Out - Domestic Heavyweight 150+'                        => 'AIT - Next Flight Out - Domestic Heavyweight 150+',
          'AIT - Next Day AM (before noon) - Domestic Heavyweight 150+'              => 'AIT - Next Day AM (before noon) - Domestic Heavyweight 150+',
          'AIT - Next Day Standard (before 5pm) - Domestic Heavyweight 150+'         => 'AIT - Next Day Standard (before 5pm) - Domestic Heavyweight 150+',
          'AIT - 3 Day - Domestic Heavyweight 150+'                                  => 'AIT - 3 Day - Domestic Heavyweight 150+',
          'AIT - Deferred Service (3-5 days) - Domestic Heavyweight 150+'            => 'AIT - Deferred Service (3-5 days) - Domestic Heavyweight 150+',
          'AIT - Less Than TruckLoad'                                                => 'AIT - Less Than TruckLoad',
          'AIT - Surface Express (3-5 days) - Domestic Heavyweight 150+'             => 'AIT - Surface Express (3-5 days) - Domestic Heavyweight 150+',
          'AIT - Ground Package (3-5) - Domestic Heavyweight 150+'                   => 'AIT - Ground Package (3-5) - Domestic Heavyweight 150+',
          'AIT - White Glove Premier - Domestic Heavyweight 150+'                    => 'AIT - White Glove Premier - Domestic Heavyweight 150+',
          'AIT - White Glove Classic - Domestic Heavyweight 150+'                    => 'AIT - White Glove Classic - Domestic Heavyweight 150+',
          'AIT - White Glove Signature - Domestic Heavyweight 150+'                  => 'AIT - White Glove Signature - Domestic Heavyweight 150+',
          'AIT - Next Day Overnight by 8:30am - Small Package 1-150'                 => 'AIT - Next Day Overnight by 8:30am - Small Package 1-150',
          'AIT - Next Day Overnight by 10:30am - Small Package 1-150'                => 'AIT - Next Day Overnight by 10:30am - Small Package 1-150',
          'AIT - Next Day Standard (before 5pm) - Small Package 1-150'               => 'AIT - Next Day Standard (before 5pm) - Small Package 1-150',
          'AIT - 2 Day - Small Package 1-150'                                        => 'AIT - 2 Day - Small Package 1-150',
          'AIT - 3 Day - Small Package 1-150'                                        => 'AIT - 3 Day - Small Package 1-150',
          'AIT - Threshold Delivery - 1 Day'                                         => 'AIT - Threshold Delivery - 1 Day',
          'AIT - Threshold Delivery - 2 Day'                                         => 'AIT - Threshold Delivery - 2 Day',
          'AIT - Threshold Delivery - 3 Day'                                         => 'AIT - Threshold Delivery - 3 Day',
          'AIT - Threshold Delivery - 4 Day'                                         => 'AIT - Threshold Delivery - 4 Day',
          'AIT - Threshold Delivery - 5 Day'                                         => 'AIT - Threshold Delivery - 5 Day',
          'AIT - White Glove Delivery - 1 Day'                                       => 'AIT - White Glove Delivery - 1 Day',
          'AIT - White Glove Delivery - 2 Day'                                       => 'AIT - White Glove Delivery - 2 Day',
          'AIT - White Glove Delivery - 3 Day'                                       => 'AIT - White Glove Delivery - 3 Day',
          'AIT - White Glove Delivery - 4 Day'                                       => 'AIT - White Glove Delivery - 4 Day',
          'AIT - White Glove Delivery - 5 Day'                                       => 'AIT - White Glove Delivery - 5 Day',
          'AIT - Room of Choice – 5 Day'                                             => 'AIT - Room of Choice – 5 Day',
          'Company Truck - Standard Delivery'                                        => 'Company Truck - Standard Delivery',
          'Local Courier - Standard Delivery'                                        => 'Local Courier - Standard Delivery',
          'Will Call'                                                                => 'Will Call',
          'FedEx 2 Day Saturday Delivery'                                            => 'FedEx 2 Day Saturday Delivery',
          'FedEx First Overnight Saturday Delivery'                                  => 'FedEx First Overnight Saturday Delivery',
          'Team World Wide - Threshold Delivery - 2 Man Service'                     => 'Team World Wide - Threshold Delivery - 2 Man Service',
          'Team World Wide - Curbside Delivery'                                      => 'Team World Wide - Curbside Delivery',
          'Team World Wide - White Glove - 1 Man Service'                            => 'Team World Wide - White Glove - 1 Man Service',
          'Team World Wide - White Glove - 2 Man Service'                            => 'Team World Wide - White Glove - 2 Man Service',
          'Team World Wide - White Glove - 15 Min. Set Up and Assembly'              => 'Team World Wide - White Glove - 15 Min. Set Up and Assembly',
          'Team World Wide - Threshold Delivery'                                     => 'Team World Wide - Threshold Delivery',
          'UPS Freight LTL'                                                          => 'UPS Freight LTL',
          'UPS Freight LTL - Guaranteed'                                             => 'UPS Freight LTL - Guaranteed',
          'UPS Freight LTL - Guaranteed A.M.'                                        => 'UPS Freight LTL - Guaranteed A.M.',
          'UPS Freight - UPS Standard LTL'                                           => 'UPS Freight - UPS Standard LTL',
          'FedEx SmartPost'                                                          => 'FedEx SmartPost',
          'USPS - Priority Mail Flat Rate Padded Envelope'                           => 'USPS - Priority Mail Flat Rate Padded Envelope',
          'USPS - Priority Mail Regional Rate Box A'                                 => 'USPS - Priority Mail Regional Rate Box A',
          'USPS - Priority Mail Regional Rate Box B'                                 => 'USPS - Priority Mail Regional Rate Box B',
          'New Penn - Standard Freight Service'                                      => 'New Penn - Standard Freight Service',
          'New Penn - Standard Freight Service'                                      => 'New Penn - Standard Freight Service',
          'Estes - LTL Standard Transit: 5PM'                                        => 'Estes - LTL Standard Transit: 5PM',
          'Estes - Standard Transit Plus: 5PM Guaranteed'                            => 'Estes - Standard Transit Plus: 5PM Guaranteed',
          'Estes - Standard Transit Plus: 12PM'                                      => 'Estes - Standard Transit Plus: 12PM',
          'Estes - Standard Transit Plus: 12PM Guaranteed'                           => 'Estes - Standard Transit Plus: 12PM Guaranteed',
          'Estes - Standard Transit Plus: 10AM'                                      => 'Estes - Standard Transit Plus: 10AM',
          'Estes - Standard Transit Plus: 10AM Guaranteed'                           => 'Estes - Standard Transit Plus: 10AM Guaranteed',
          'Watkins and Shepard - Premium'                                            => 'Watkins and Shepard - Premium',
          'Multiple Carriers'                                                        => 'Multiple Carriers'
        );
    }

    private function _isValidRateResponse($rateResponse)
    {
        return $rateResponse && $rateResponse['response'] && $rateResponse['response']->isSuccessful();
    }

    private function _getShippingPriceWithMarkup($rate, $rateResponse)
    {
        $price = $this->getTotalPriceFromDetailedResponse($rate);
        return $this->shippingPriceWithMarkup($price, $rateResponse['data']['flat_markup'], $rateResponse['data']['percent_markup']);
    }

    private function _getRates($rateResponse)
    {
        return json_decode($rateResponse['response']->getBody());
    }
}
