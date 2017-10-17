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

        foreach( $groupedItems as $key => $data) {
            $destination_address = array(
                'zip'            =>  $to_zip = $request->getDestPostcode(),
                'is_residential' =>  'true'
            );

            $rateRequest = array(
                'origin_address'      => $data['origin_address'],
                'destination_address' => $destination_address,
                'items'               => $data['items'],
                'apply_rules'         =>'true',
                'carrier_type_filter' => (count($data['carrier_type_filter']) > 0 ? $data['carrier_type_filter'] : null)
            );

            if (count($groupedItems) > 1){
                $rateRequest['rate_filter'] = 'best';
            }

            $rateResponsesArray[] = $this->getRates($rateRequest);
        }

        $rateArray = new stdClass;
        $rateArray->rates = array();

        if (count($groupedItems) > 1){
            $partialRatesArray = array();
            $combinedRate = $this->_buildCombinedRate();

            foreach ( $rateResponsesArray as $rateResponse ) {
                if($rateResponse && $rateResponse->isSuccessful()) {
                    $rate = json_decode($rateResponse->getBody())->rates[0];
                    $partialRatesArray[] = $rate;
                    $combinedRate->price += $rate->price;
                    $combinedRate->id[] = $rate->id;
                    $combinedRate->est_delivery_date = $rate->est_delivery_date;
                }
            }

            if (count($partialRatesArray) == count($groupedItems)){
                $rateArray->rates[] = $combinedRate;
            }
        } else {
            $rateResponse = $rateResponsesArray[0];
            if($rateResponse && $rateResponse->isSuccessful()) {
                $rateArray = json_decode($rateResponse->getBody());
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
        $rate->setMethod($shRate->service_name);
        // $rate->setMethod($shRate->carrier. '-' . $shRate->service_name);

        $service_title = $shRate->service_name;
        if (substr($shRate->service_name, 0, strlen($shRate->carrier)) !== $shRate->carrier){
            $service_title = $shRate->carrier . ' - ' . $shRate->service_name;
        }

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
                );
            }

            $carrier_type = $this->getProductCarrierType($product);
            $groupKey = $origin_address['zip'] . ' ' . implode(',', $carrier_type);

            if (!array_key_exists($groupKey, $itemsGrouped)){
                $itemsGrouped[$groupKey] = array(
                    'origin_address'      => $origin_address,
                    'carrier_type_filter' => $carrier_type,
                    'items'               => array()
                );
            }

            $item_weight = $item->getWeight();

            $is_packed = ($product->getData('shiphawk_item_is_packed') == 1);

            if ( !$is_packed && $product->getData('shiphawk_type_of_product_value') && $product->getData('shiphawk_type_of_product')){
                $item_type = 'unpacked';
                $product->getData('shiphawk_type_of_product_value');
            } else {
                $item_type = $item_weight <= 70 ? 'parcel' : 'handling_unit';
            }

            $handling_unit_type = '';
            if ($item_type == 'handling_unit'){
                $handling_unit_type = 'box';
            }

            $itemsGrouped[$groupKey]['items'][] = array(
                'product_sku'           => $product->getData($skuColumn),
                'quantity'              => $item->getQty(),
                'value'                 => $product->getData('shiphawk_item_value') ? $product->getData('shiphawk_item_value') : $itemObject->getPrice(),
                'length'                => $product->getData('shiphawk_length')     ? $product->getData('shiphawk_length')     : $itemObject->getLength(),
                'width'                 => $product->getData('shiphawk_width')      ? $product->getData('shiphawk_width')      : $itemObject->getWidth(),
                'height'                => $product->getData('shiphawk_height')     ? $product->getData('shiphawk_height')     : $itemObject->getHeight(),
                'freight_class'         => $this->getSelectAttributeValue($product, 'shiphawk_freight_class'),
                'weight'                => $item_weight <= 70 ? $item_weight * 16 : $item_weight,
                // 'item_type'             => $item_type,
                'type'                  => $item_type,
                'handling_unit_type'    => $handling_unit_type,
                'unpacked_item_type_id' => $product->getData('shiphawk_type_of_product_value')
            );
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


    public function getAllowedMethods()
    {
        return array(
            'Standard Freight Service'                                      => 'Standard Freight Service',
            'UPS Worldwide Express Plus'                                    => 'UPS Worldwide Express Plus',
            'UPS Second Day Air'                                            => 'UPS Second Day Air',
            'UPS Three-Day Select'                                          => 'UPS Three-Day Select',
            'UPS Second Day Air A.M.'                                       => 'UPS Second Day Air A.M.',
            'UPS Next Day Air Early'                                        => 'UPS Next Day Air Early',
            'UPS Next Day Air Saver'                                        => 'UPS Next Day Air Saver',
            'UPS Ground'                                                    => 'UPS Ground',
            'UPS Next Day Air'                                              => 'UPS Next Day Air',
            'UPS SurePost'                                                  => 'UPS SurePost',
            'UPS Worldwide Express'                                         => 'UPS Worldwide Express',
            'UPS Worldwide Express Freight'                                 => 'UPS Worldwide Express Freight',
            'UPS Standard'                                                  => 'UPS Standard',
            'UPS Worldwide Expedited'                                       => 'UPS Worldwide Expedited',
            'UPS Worldwide Saver'                                           => 'UPS Worldwide Saver',
            'FedEx Express Saver'                                           => 'FedEx Express Saver',
            'FedEx 2 Day'                                                   => 'FedEx 2 Day',
            'FedEx Priority Overnight Saturday Delivery'                    => 'FedEx Priority Overnight Saturday Delivery',
            'FedEx Standard Overnight'                                      => 'FedEx Standard Overnight',
            'FedEx Ground'                                                  => 'FedEx Ground',
            'FedEx International Priority'                                  => 'FedEx International Priority',
            'FedEx International First'                                     => 'FedEx International First',
            'FedEx International Economy'                                   => 'FedEx International Economy',
            'FedEx Ground Home Delivery'                                    => 'FedEx Ground Home Delivery',
            'FedEx First Overnight Saturday Delivery'                       => 'FedEx First Overnight Saturday Delivery',
            'FedEx First Overnight'                                         => 'FedEx First Overnight',
            'FedEx 2 Day Am'                                                => 'FedEx 2 Day Am',
            'FedEx SmartPost'                                               => 'FedEx SmartPost',
            'FedEx 2 Day Saturday Delivery'                                 => 'FedEx 2 Day Saturday Delivery',
            'FedEx International Ground'                                    => 'FedEx International Ground',
            'FedEx Priority Overnight'                                      => 'FedEx Priority Overnight',
            'Guaranteed Freight Service'                                    => 'Guaranteed Freight Service',
            'Flatbed'                                                       => 'Flatbed',
            'Premier'                                                       => 'Premier',
            'Standard One Man'                                              => 'Standard One Man',
            'Standard Two Man'                                              => 'Standard Two Man',
            'Deluxe'                                                        => 'Deluxe',
            'Basic'                                                         => 'Basic',
            'Standard Transit Plus: 10AM'                                   => 'Standard Transit Plus: 10AM',
            'Standard Transit Plus: 12PM Guaranteed'                        => 'Standard Transit Plus: 12PM Guaranteed',
            'Standard Transit Plus: 12PM'                                   => 'Standard Transit Plus: 12PM',
            'Standard Transit Plus: 5PM Guaranteed'                         => 'Standard Transit Plus: 5PM Guaranteed',
            'Standard Transit Plus: 10AM Guaranteed'                        => 'Standard Transit Plus: 10AM Guaranteed',
            'LTL Standard Transit: 5PM'                                     => 'LTL Standard Transit: 5PM',
            'White Glove'                                                   => 'White Glove',
            'Guaranteed Delivery by 5PM'                                    => 'Guaranteed Delivery by 5PM',
            'RGN'                                                           => 'RGN',
            'Stepdeck'                                                      => 'Stepdeck',
            'Priority Mail International Medium Flat Rate Box'              => 'Priority Mail International Medium Flat Rate Box',
            'Priority Mail International Large Flat Rate Box'               => 'Priority Mail International Large Flat Rate Box',
            'Priority Mail International Flat Rate Envelope'                => 'Priority Mail International Flat Rate Envelope',
            'Priority Mail Express International Flat Rate Envelope'        => 'Priority Mail Express International Flat Rate Envelope',
            'Priority Mail International Flat Rate Legal Envelope'          => 'Priority Mail International Flat Rate Legal Envelope',
            'Priority Mail Flat Rate Envelope'                              => 'Priority Mail Flat Rate Envelope',
            'Priority Mail Express International Flat Rate Legal Envelope'  => 'Priority Mail Express International Flat Rate Legal Envelope',
            'Global Express Guaranteed'                                     => 'Global Express Guaranteed',
            'Priority Mail Express International'                           => 'Priority Mail Express International',
            'Priority Mail Large Flat Rate Box'                             => 'Priority Mail Large Flat Rate Box',
            'Priority Mail International'                                   => 'Priority Mail International',
            'First Class Mail International'                                => 'First Class Mail International',
            'Priority Mail International Flat Rate Padded Envelope'         => 'Priority Mail International Flat Rate Padded Envelope',
            'Priority Mail Express International Flat Rate Padded Envelope' => 'Priority Mail Express International Flat Rate Padded Envelope',
            'Priority Mail Medium Flat Rate Box'                            => 'Priority Mail Medium Flat Rate Box',
            'Priority Mail Small Flat Rate Box'                             => 'Priority Mail Small Flat Rate Box',
            'First Class Package International Service'                     => 'First Class Package International Service',
            'Priority Mail Flat Rate Padded Envelope'                       => 'Priority Mail Flat Rate Padded Envelope',
            'Priority Mail International Small Flat Rate Box'               => 'Priority Mail International Small Flat Rate Box',
            'Priority Mail Regional Rate Box A'                             => 'Priority Mail Regional Rate Box A',
            'Priority Mail'                                                 => 'Priority Mail',
            'Priority Mail Regional Rate Box B'                             => 'Priority Mail Regional Rate Box B',
            'Priority Mail Express Flat Rate Envelope'                      => 'Priority Mail Express Flat Rate Envelope',
            'Priority Mail Flat Rate Legal Envelope'                        => 'Priority Mail Flat Rate Legal Envelope',
            'Priority Mail Express'                                         => 'Priority Mail Express',
            'Parcel Select Ground'                                          => 'Parcel Select Ground',
            'Priority Mail Express Flat Rate Legal Envelope'                => 'Priority Mail Express Flat Rate Legal Envelope',
            'Media Mail'                                                    => 'Media Mail',
            'Library Mail'                                                  => 'Library Mail',
            'First-Class Mail'                                              => 'First-Class Mail',
            'Standard Courier Service'                                      => 'Standard Courier Service',
            'Standard Vehicle Service'                                      => 'Standard Vehicle Service',
            'Local Delivery'                                                => 'Local Delivery',
            'FedEx Freight Economy'                                         => 'FedEx Freight Economy',
            'FedEx Freight Priority'                                        => 'FedEx Freight Priority',
            'White Glove'                                                   => 'White Glove',
            'UPS Freight LTL'                                               => 'UPS Freight LTL',
            'UPS Freight LTL - Guaranteed'                                  => 'UPS Freight LTL - Guaranteed',
            'UPS Freight LTL - Guaranteed A.M.'                             => 'UPS Freight LTL - Guaranteed A.M.',
            'UPS Standard LTL'                                              => 'UPS Standard LTL',
            'Basic Service Delivery'                                        => 'Basic Service Delivery',
            'Premier Service Delivery'                                      => 'Premier Service Delivery',
            'Room of Choice'                                                => 'Room of Choice',
            'Over the Threshold'                                            => 'Over the Threshold',
            'Deluxed Delivery'                                              => 'Deluxed Delivery',
            'Threshold'                                                     => 'Threshold',
            'Premium'                                                       => 'Premium',
            'White Glove Delivery'                                          => 'White Glove Delivery',
            'Same-Day Freight Service'                                      => 'Same-Day Freight Service',
            'FedEx 1 Day Freight Saturday Delivery'                         => 'FedEx 1 Day Freight Saturday Delivery',
            'FedEx 2 Day Freight'                                           => 'FedEx 2 Day Freight',
            'FedEx 3 Day Freight'                                           => 'FedEx 3 Day Freight',
            'FedEx 1 Day Freight'                                           => 'FedEx 1 Day Freight',
            'White Glove - 15 Min. Set Up and Assembly'                     => 'White Glove - 15 Min. Set Up and Assembly',
            'Threshold Delivery'                                            => 'Threshold Delivery',
            'White Glove - 2 Man Service'                                   => 'White Glove - 2 Man Service',
            'White Glove - 1 Man Service'                                   => 'White Glove - 1 Man Service',
            'Curbside Delivery'                                             => 'Curbside Delivery',
            'Threshold Delivery - 2 Man Service'                            => 'Threshold Delivery - 2 Man Service',
            'JETLINE'                                                       => 'JETLINE',
            'EXPRESS 10:30'                                                 => 'EXPRESS 10:30',
            'DOMESTIC EXPRESS'                                              => 'DOMESTIC EXPRESS',
            'ECONOMY SELECT'                                                => 'ECONOMY SELECT',
            'EXPRESS 9:00'                                                  => 'EXPRESS 9:00',
            'EXPRESS EASY'                                                  => 'EXPRESS EASY',
            'FREIGHT WORLDWIDE'                                             => 'FREIGHT WORLDWIDE',
            'EXPRESS WORLDWIDE'                                             => 'EXPRESS WORLDWIDE',
            'EXPRESS 12:00'                                                 => 'EXPRESS 12:00',
            'Sunrise'                                                       => 'Sunrise',
            'Same Day'                                                      => 'Same Day',
            'Ground'                                                        => 'Ground',
            'Palletized Freight'                                            => 'Palletized Freight',
            'Sunrise Gold'                                                  => 'Sunrise Gold',
            '3 Day Delivery'                                                => '3 Day Delivery',
            '2 Day Delivery'                                                => '2 Day Delivery',
            'Next Day AM Delivery'                                          => 'Next Day AM Delivery',
            'Economy Service'                                               => 'Economy Service',
            'Next Day PM Delivery'                                          => 'Next Day PM Delivery',
            'Threshold Delivery - 3 Day'                                    => 'Threshold Delivery - 3 Day',
            'Threshold Delivery - 4 Day'                                    => 'Threshold Delivery - 4 Day',
            'Threshold Delivery - 5 Day'                                    => 'Threshold Delivery - 5 Day',
            'White Glove Delivery - 1 Day'                                  => 'White Glove Delivery - 1 Day',
            'White Glove Delivery - 2 Day'                                  => 'White Glove Delivery - 2 Day',
            'White Glove Delivery - 3 Day'                                  => 'White Glove Delivery - 3 Day',
            'White Glove Delivery - 4 Day'                                  => 'White Glove Delivery - 4 Day',
            'White Glove Delivery - 5 Day'                                  => 'White Glove Delivery - 5 Day',
            'Room of Choice – 5 Day'                                        => 'Room of Choice – 5 Day',
            'White Glove Premier - Domestic Heavyweight 150+'               => 'White Glove Premier - Domestic Heavyweight 150+',
            'Ground Package (3-5) - Domestic Heavyweight 150+'              => 'Ground Package (3-5) - Domestic Heavyweight 150+',
            'Surface Express (3-5 days) - Domestic Heavyweight 150+'        => 'Surface Express (3-5 days) - Domestic Heavyweight 150+',
            'Less Than TruckLoad'                                           => 'Less Than TruckLoad',
            'Deferred Service (3-5 days) - Domestic Heavyweight 150+'       => 'Deferred Service (3-5 days) - Domestic Heavyweight 150+',
            '3 Day - Domestic Heavyweight 150+'                             => '3 Day - Domestic Heavyweight 150+',
            'Next Day Standard (before 5pm) - Domestic Heavyweight 150+'    => 'Next Day Standard (before 5pm) - Domestic Heavyweight 150+',
            'Next Day AM (before noon) - Domestic Heavyweight 150+'         => 'Next Day AM (before noon) - Domestic Heavyweight 150+',
            'Next Flight Out - Domestic Heavyweight 150+'                   => 'Next Flight Out - Domestic Heavyweight 150+',
            'Same Day - Domestic Heavyweight 150+'                          => 'Same Day - Domestic Heavyweight 150+',
            'Next Day Overnight by 10:30am - Small Package 1-150'           => 'Next Day Overnight by 10:30am - Small Package 1-150',
            '2 Day - Small Package 1-150'                                   => '2 Day - Small Package 1-150',
            '3 Day - Small Package 1-150'                                   => '3 Day - Small Package 1-150',
            'Threshold Delivery - 1 Day'                                    => 'Threshold Delivery - 1 Day',
            'Threshold Delivery - 2 Day'                                    => 'Threshold Delivery - 2 Day',
            '2 Day - Domestic Heavyweight 150+'                             => '2 Day - Domestic Heavyweight 150+',
            'White Glove Classic - Domestic Heavyweight 150+'               => 'White Glove Classic - Domestic Heavyweight 150+',
            'White Glove Signature - Domestic Heavyweight 150+'             => 'White Glove Signature - Domestic Heavyweight 150+',
            'Next Day Overnight by 8:30am - Small Package 1-150'            => 'Next Day Overnight by 8:30am - Small Package 1-150',
            'Next Day Standard (before 5pm) - Small Package 1-150'          => 'Next Day Standard (before 5pm) - Small Package 1-150',
            '4 Day - Domestic Heavyweight 150+'                             => '4 Day - Domestic Heavyweight 150+',
            '2 Day AM (before noon) - Domestic Heavyweight 150+'            => '2 Day AM (before noon) - Domestic Heavyweight 150+',
            'Standard Delivery'                                             => 'Standard Delivery',
            'Will Call'                                                     => 'Will Call',
            'Multiple Carriers'                                             => 'Multiple Carriers'
        );
    }
}
