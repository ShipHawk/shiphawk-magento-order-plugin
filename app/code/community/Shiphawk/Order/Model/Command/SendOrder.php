<?php

class Shiphawk_Order_Model_Command_SendOrder
{
    public function execute(Mage_Sales_Model_Order $order)
    {
        Mage::log('building order object for Shiphawk');
        $url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $key = Mage::getStoreConfig('shiphawk/order/api_key');
        $client = new Zend_Http_Client($url . 'orders');
        $client->setHeaders('X-Api-Key', $key);


        $itemsRequest = [];
        $shippingRateId = '';

        Mage::log('rates array:...');
        Mage::log(Mage::getSingleton('core/session')->getSHRateAarray());

        $SHRates = Mage::getSingleton('core/session')->getSHRateAarray();
        foreach($SHRates as $rateRow){
            if(($rateRow->carrier . ' - ' . $rateRow->service_level)  == $order->getShippingDescription()){
                $shippingRateId = $rateRow->id;
            }
        }

        $skuColumn = Mage::getStoreConfig('shiphawk/order/sku_column');
        foreach ($order->getAllItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $itemsRequest[] = array(

                'name' => $item->getName(),
                'sku' => $item->getData($skuColumn),
                'quantity' => $item->getQtyOrdered(),
                'price' => $item->getPrice(),
                'length' => $item->getLength(),
                'width' => $item->getWidth(),
                'height' => $item->getHeight(),
                'weight' => $item->getWeight(),
            );
        }

        $orderRequest = json_encode(
            array(
                'order_number' => $order->getIncrementId(),
                'source_system' => 'magento',
                'source_system_id' => $order->getEntityId(),
                'source_system_processed_at' => date('Y-m-d H:i:s'),
                'requested_rate_id' => $shippingRateId,
                'requested_shipping_details'=> $order->getShippingDescription(),
                'origin_address' => $this->getOriginAddress(),
                'destination_address' => $this->prepareAddress($order->getShippingAddress()),
                'order_line_items' => $itemsRequest,
                'total_price' => $order->getGrandTotal(),
                'shipping_price' => $order->getShippingAmount(),
                'tax_price' => $order->getTaxAmount(),
                'items_price' => $order->getSubtotal(),
                'status' => Mage::getSingleton('shiphawk_order/statusMapper')->map($order->getStatus()),
            )
        );

        Mage::log('ShipHawk Request: ' . $orderRequest, Zend_Log::INFO, 'shiphawk_order.log', true);
        $client->setRawData($orderRequest, 'application/json');
        try {
            $response = $client->request(Zend_Http_Client::POST);
            Mage::log('ShipHawk Response: ' . var_export($response, true), Zend_Log::INFO, 'shiphawk_order.log', true);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    protected function prepareAddress(Mage_Sales_Model_Order_Address $address)
    {
        return array(
            'name' => $address->getFirstname() . ' '
                . $address->getMiddlename() . ' '
                . $address->getLastname(),
            'company' => $address->getCompany(),
            'street1' => $address->getStreet1(),
            'street2' => $address->getStreet2(),
            'phone_number' => $address->getTelephone(),
            'city' => $address->getCity(),
            'state' => $address->getRegionCode(),
            'country' => $address->getCountryId(),
            'zip'  => $address->getPostcode(),
            'email' => $address->getEmail()
        );
    }

    protected function getOriginAddress()
    {
        return array(
            'name' => Mage::getStoreConfig('general/store_information/name'),
            'phone_number' => Mage::getStoreConfig('general/store_information/phone'),
            'street1' => Mage::getStoreConfig('shipping/origin/street_line1'),
            'street2' => Mage::getStoreConfig('shipping/origin/street_line2'),
            'city' => Mage::getStoreConfig('shipping/origin/city'),
            'state' => Mage::getModel('directory/region')
                ->load(Mage::getStoreConfig('shipping/origin/region_id'))
                ->getCode(),
            'country' => Mage::getStoreConfig('shipping/origin/country_id'),
            'zip' => Mage::getStoreConfig('shipping/origin/postcode'),
        );
    }
}
