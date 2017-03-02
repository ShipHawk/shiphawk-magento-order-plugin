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

        $items = $this->getItems($request);
        $rateRequest = array(
            'items' => $items,
            'origin_address'=> array(
                'zip'=>Mage::getStoreConfig('shipping/origin/postcode')
            ),
            'destination_address'=> array(
                'zip'               =>  $to_zip = $request->getDestPostcode(),
                'is_residential'    =>  'true'
            ),
            'apply_rules'=>'true'
        );

        Mage::log($rateRequest);

        $rateResponse = $this->getRates($rateRequest);

        Mage::log($rateResponse);

        if($rateResponse && $rateResponse->isSuccessful())
        {
            $rateArray = json_decode($rateResponse->getBody());
            Mage::log($rateArray);
        }

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

            if($option = $item->getOptionByCode('simple_product')) {

                $product_id = $option->getProductId();
                $product = Mage::getModel('catalog/product')->load($product_id);
                //commenting out log statment to make the logs more readable. Uncomment when debugging rating.
                //Mage::log('product data: ' . var_export($product->debug(), true), Zend_Log::INFO, 'shiphawk_rates.log', true);
                $item_weight = $item->getWeight();
                $items[] = array(
                    'product_sku' => $product->getData($skuColumn),
                    'quantity' => $item->getQty(),
                    'value' => $option->getPrice(),
                    'length' => $option->getLength(),
                    'width' => $option->getWidth(),
                    'height' => $option->getHeight(),
                    'weight' => $item_weight <= 70 ? $item_weight * 16 : $item_weight,
                    'item_type' => $item_weight <= 70 ? 'parcel' : 'handling_unit',
                    'handling_unit_type' => $item_weight <= 70 ? '' : 'box'
                );
            }
            else if( $item->getTypeId() != 'configurable' && !$item->getParentItemId() ){
                $product_id = $item->getProductId();
                $product = Mage::getModel('catalog/product')->load($product_id);
                //commenting out log statment to make the logs more readable. Uncomment when debugging rating.
                //Mage::log('product data: ' . var_export($product->debug(), true), Zend_Log::INFO, 'shiphawk_rates.log', true);
                $item_weight = $item->getWeight();
                $items[] = array(
                    'product_sku' => $product->getData($skuColumn),
                    'quantity' => $item->getQty(),
                    'value' => $item->getPrice(),
                    'length' => $item->getLength(),
                    'width' => $item->getWidth(),
                    'height' => $item->getHeight(),
                    'weight' => $item_weight <= 70 ? $item_weight * 16 : $item_weight,
                    'item_type' => $item_weight <= 70 ? 'parcel' : 'handling_unit',
                    'handling_unit_type' => $item_weight <= 70 ? '' : 'box'
                );
            }



        }

        Mage::log($items);

        return $items;

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
            'UPS Worldwide Saver'                                           => 'UPS Worldwide Saver'
        );
    }
}
