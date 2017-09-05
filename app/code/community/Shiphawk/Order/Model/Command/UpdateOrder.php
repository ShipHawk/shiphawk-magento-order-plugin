<?php

class Shiphawk_Order_Model_Command_UpdateOrder
{
    public function execute(Mage_Sales_Model_Order $order)
    {
        $url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $key = Mage::getStoreConfig('shiphawk/order/api_key');

        $client = new Zend_Http_Client($url . 'orders/' . $order->getIncrementId());
        $client->setHeaders('X-Api-Key', $key);

        $notes = [];
        foreach ($order->getAllStatusHistory() as $note){
            $notes[] = array(
                'body'       => $note->comment,
                'created_at' => $note->created_at,
                'tag'        => 'magento'
            );
        }

        $orderRequest = json_encode(
            array(
                'notes' => $notes
            )
        );

        Mage::log('ShipHawk Request: ' . $client->getUri(true) . $orderRequest, Zend_Log::INFO, 'shiphawk_order.log', true);
        $client->setRawData($orderRequest, 'application/json');
        try {
            $response = $client->request(Zend_Http_Client::POST);
            Mage::log('ShipHawk Response: ' . var_export($response, true), Zend_Log::INFO, 'shiphawk_order.log', true);
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }
}
