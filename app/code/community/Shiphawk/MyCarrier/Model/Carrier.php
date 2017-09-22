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
                'carrier_type'        => $data['carrier_type']
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
        $rate->setMethodTitle($shRate->service_name);

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

    public function getItems($request)
    {
        $items = array();
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

            $item_weight = $item->getWeight();
            $items[] = array(
                'product_sku'        => $product->getData($skuColumn),
                'quantity'           => $item->getQty(),
                'value'              => $itemObject->getPrice(),
                'length'             => $itemObject->getLength(),
                'width'              => $itemObject->getWidth(),
                'height'             => $itemObject->getHeight(),
                'weight'             => $item_weight <= 70 ? $item_weight * 16 : $item_weight,
                'item_type'          => $item_weight <= 70 ? 'parcel' : 'handling_unit',
                'handling_unit_type' => $item_weight <= 70 ? '' : 'box'
            );
        }

        Mage::log($items);

        return $items;
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

            $carrier_type = $this->getSelectAttributeValue($product, 'shiphawk_carrier_type');
            $groupKey = $origin_address['zip'] . ' ' . $carrier_type;

            if (!$itemsGrouped[$groupKey]){
                $itemsGrouped[$groupKey] = array(
                    'origin_address' => $origin_address,
                    'carrier_type'   => $carrier_type,
                    'items'          => array()
                );
            }

            $item_weight = $item->getWeight();

            $itemsGrouped[$groupKey]['items'][] = array(
                'product_sku'        => $product->getData($skuColumn),
                'quantity'           => $item->getQty(),
                'value'              => $product->getData('shiphawk_item_value') ? $product->getData('shiphawk_item_value') : $itemObject->getPrice(),
                'length'             => $product->getData('shiphawk_length')     ? $product->getData('shiphawk_length')     : $itemObject->getLength(),
                'width'              => $product->getData('shiphawk_width')      ? $product->getData('shiphawk_width')      : $itemObject->getWidth(),
                'height'             => $product->getData('shiphawk_height')     ? $product->getData('shiphawk_height')     : $itemObject->getHeight(),
                'freight_class'      => $this->getSelectAttributeValue($product, 'shiphawk_freight_class'),
                'weight'             => $item_weight <= 70 ? $item_weight * 16 : $item_weight,
                'item_type'          => $item_weight <= 70 ? 'parcel' : 'handling_unit',
                'handling_unit_type' => $item_weight <= 70 ? '' : 'box'
            );
        }

        return $itemsGrouped;

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
            'FedEx 2 Day'                                                   => 'FedEx 2 Day',
            'FedEx 2 Day Am'                                                => 'FedEx 2 Day Am',
            'FedEx Express Saver'                                           => 'FedEx Express Saver',
            'FedEx First Overnight'                                         => 'FedEx First Overnight',
            'FedEx First Overnight Saturday Delivery'                       => 'FedEx First Overnight Saturday Delivery',
            'FedEx Ground'                                                  => 'FedEx Ground',
            'FedEx Ground Home Delivery'                                    => 'FedEx Ground Home Delivery',
            'FedEx International Economy'                                   => 'FedEx International Economy',
            'FedEx International First'                                     => 'FedEx International First',
            'FedEx International Ground'                                    => 'FedEx International Ground',
            'FedEx International Priority'                                  => 'FedEx International Priority',
            'FedEx Priority Overnight'                                      => 'FedEx Priority Overnight',
            'FedEx Priority Overnight Saturday Delivery'                    => 'FedEx Priority Overnight Saturday Delivery',
            'FedEx Standard Overnight'                                      => 'FedEx Standard Overnight',
            'First Class Mail International'                                => 'First Class Mail International',
            'First Class Package International Service'                     => 'First Class Package International Service',
            'First-Class Mail'                                              => 'First-Class Mail',
            'Global Express Guaranteed'                                     => 'Global Express Guaranteed',
            'Library Mail'                                                  => 'Library Mail',
            'Media Mail'                                                    => 'Media Mail',
            'Parcel Select Ground'                                          => 'Parcel Select Ground',
            'Priority Mail'                                                 => 'Priority Mail',
            'Priority Mail Express'                                         => 'Priority Mail Express',
            'Priority Mail Express Flat Rate Envelope'                      => 'Priority Mail Express Flat Rate Envelope',
            'Priority Mail Express Flat Rate Legal Envelope'                => 'Priority Mail Express Flat Rate Legal Envelope',
            'Priority Mail Express International'                           => 'Priority Mail Express International',
            'Priority Mail Express International Flat Rate Envelope'        => 'Priority Mail Express International Flat Rate Envelope',
            'Priority Mail Express International Flat Rate Legal Envelope'  => 'Priority Mail Express International Flat Rate Legal Envelope',
            'Priority Mail Express International Flat Rate Padded Envelope' => 'Priority Mail Express International Flat Rate Padded Envelope',
            'Priority Mail Flat Rate Envelope'                              => 'Priority Mail Flat Rate Envelope',
            'Priority Mail Flat Rate Legal Envelope'                        => 'Priority Mail Flat Rate Legal Envelope',
            'Priority Mail International'                                   => 'Priority Mail International',
            'Priority Mail International Flat Rate Envelope'                => 'Priority Mail International Flat Rate Envelope',
            'Priority Mail International Flat Rate Legal Envelope'          => 'Priority Mail International Flat Rate Legal Envelope',
            'Priority Mail International Flat Rate Padded Envelope'         => 'Priority Mail International Flat Rate Padded Envelope',
            'Priority Mail International Large Flat Rate Box'               => 'Priority Mail International Large Flat Rate Box',
            'Priority Mail International Medium Flat Rate Box'              => 'Priority Mail International Medium Flat Rate Box',
            'Priority Mail International Small Flat Rate Box'               => 'Priority Mail International Small Flat Rate Box',
            'Priority Mail Large Flat Rate Box'                             => 'Priority Mail Large Flat Rate Box',
            'Priority Mail Medium Flat Rate Box'                            => 'Priority Mail Medium Flat Rate Box',
            'Priority Mail Small Flat Rate Box'                             => 'Priority Mail Small Flat Rate Box',
            'UPS Ground'                                                    => 'UPS Ground',
            'UPS Next Day Air'                                              => 'UPS Next Day Air',
            'UPS Next Day Air Early'                                        => 'UPS Next Day Air Early',
            'UPS Next Day Air Saver'                                        => 'UPS Next Day Air Saver',
            'UPS Second Day Air'                                            => 'UPS Second Day Air',
            'UPS Second Day Air A.M.'                                       => 'UPS Second Day Air A.M.',
            'UPS Standard'                                                  => 'UPS Standard',
            'UPS SurePost'                                                  => 'UPS SurePost',
            'UPS Three-Day Select'                                          => 'UPS Three-Day Select',
            'UPS Worldwide Expedited'                                       => 'UPS Worldwide Expedited',
            'UPS Worldwide Express'                                         => 'UPS Worldwide Express',
            'UPS Worldwide Express Freight'                                 => 'UPS Worldwide Express Freight',
            'UPS Worldwide Express Plus'                                    => 'UPS Worldwide Express Plus',
            'UPS Worldwide Saver'                                           => 'UPS Worldwide Saver',
            'Multiple Carriers'                                             => 'Multiple Carriers'
        );
    }
}
