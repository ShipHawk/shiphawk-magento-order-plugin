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
        foreach($rateArray->rates as $rateRow)
        {
            $result->append($this->_buildRate($rateRow));
        }


        return $result;
    }

    protected function _buildRate($shRate)
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

        $rate->setMethod($shRate->carrier. '-' . $shRate->service_name);
        $rate->setMethodTitle($shRate->service_name);

        $rate->setPrice($shRate->price);
        $rate->setCost($shRate->price);

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
            $product_id = $item->getProductId();
            $product = Mage::getModel('catalog/product')->load($product_id);
            //commenting out log statment to make the logs more readable. Uncomment when debugging rating.
            //Mage::log('product data: ' . var_export($product->debug(), true), Zend_Log::INFO, 'shiphawk_rates.log', true);
            $items[] = array(
                'product_sku' => $product->getData($skuColumn),
                'quantity' => $item->getQty(),
                'value'         => $item->getPrice(),
                'length'        => $item->getLength(),
                'width'         => $item->getWidth(),
                'height'        => $item->getHeight(),
                'weight'        => $item->getWeight(),
                'item_type'     => $item->getWeight()  <= 70 ? 'parcel' : 'handling_unit',
                'handling_unit_type' => $item->getWeight()  <= 70 ? '' : 'box'
            );



        }

        Mage::log($items);

        return $items;

    }

    public function getAllowedMethods()
    {
        return array();
    }
}