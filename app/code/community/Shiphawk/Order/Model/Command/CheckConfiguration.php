<?php

class Shiphawk_Order_Model_Command_CheckConfiguration
{
    public function execute()
    {
        $url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $key = Mage::getStoreConfig('shiphawk/order/api_key');
        $client = new Zend_Http_Client($url . 'user?api_key=' . $key);

        $response = $client->request(Zend_Http_Client::GET);

        if ($response->isSuccessful()) {
            Mage::getSingleton('adminhtml/session')
                ->addSuccess(Mage::helper('shiphawk_order')->__('Your account successfully linked.'));
            Mage::getConfig()->saveConfig('shiphawk/order/status', 1);
        } else {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('shiphawk_order')->__('Unable to authenticate API key.'));
            Mage::getConfig()->saveConfig('shiphawk/order/status', 0);
        }
    }
}
