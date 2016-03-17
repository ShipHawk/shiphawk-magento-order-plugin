<?php

class Shiphawk_PushOrder_Model_Observer
{
    public function pushOrder($observer)
    {
        if (Mage::getStoreConfig('carriers/shiphawk_shipping/active') != 1) {
            return ;
        }

        $url = Mage::getStoreConfig('carriers/shiphawk_shipping/gateway_url');
        $key = Mage::getStoreConfig('carriers/shiphawk_shipping/api_key');
        $client = new Zend_Http_Client($url . 'orders' . $key);

        //$client->
    }
}
