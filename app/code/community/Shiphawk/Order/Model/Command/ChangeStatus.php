<?php

class Shiphawk_Order_Model_Command_ChangeStatus
{
    public function execute(Mage_Sales_Model_Order $order)
    {
        $url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $key = Mage::getStoreConfig('shiphawk/order/api_key');

        $client = new Zend_Http_Client($url . 'orders/' . $order->getIncrementId() . '/cancelled?api_key=' . $key);

        $orderRequest = json_encode(
            array(
                'source_system' => 'magento',
                'source_system_id' => $order->getEntityId(),
                'source_system_processed_at' => '',
                'canceled_at' => $order->getUpdatedAt(),
                'status' => Mage::getSingleton('shiphawk_order/statusMapper')->map($order->getStatus()),
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
