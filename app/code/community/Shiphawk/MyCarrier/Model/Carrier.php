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
                'zip'=>$to_zip = $request->getDestPostcode()
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

        $rate->setMethod($shRate->carrier. '-' . $shRate->service_level);
        $rate->setMethodTitle($shRate->service_level);

        $rate->setPrice($shRate->price);
        $rate->setCost($shRate->price);

        return $rate;
    }

    public function getRates($rateRequest)
    {
        $url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $key = Mage::getStoreConfig('shiphawk/order/api_key');

        $jsonRateRequest = json_encode($rateRequest);

        $client = new Zend_Http_Client($url . 'rates?api_key=' . $key);

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
        foreach ($request->getAllItems() as $item) {
            $items[] = array(
                'product_sku' => $item->getSku()
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