<?php

class Shiphawk_Order_Model_Command_CheckConfiguration
{
    public function execute()
    {
        $url = Mage::getStoreConfig('shiphawk/order/gateway_url');
        $key = Mage::getStoreConfig('shiphawk/order/api_key');
        $client = new Zend_Http_Client($url . 'user');
        $client->setHeaders('X-Api-Key', $key);


        if(!(Mage::getStoreConfig('general/store_information/name')) ||  !(Mage::getStoreConfig('general/store_information/phone'))){
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('shiphawk_order')->__('Missing information required for printing labels: Please add a store name and phone number under System > Configuration > General > Store Information'));
        }

        $response = $client->request(Zend_Http_Client::GET);

        if ($response->isSuccessful()) {
            Mage::getSingleton('adminhtml/session')
                ->addSuccess(Mage::helper('shiphawk_order')->__('Your account is successfully linked with Shiphawk.'));
            Mage::getConfig()->saveConfig('shiphawk/order/status', 1);
        } else {
            Mage::getSingleton('adminhtml/session')
                ->addError(Mage::helper('shiphawk_order')->__('Unable to authenticate API key.'));
            Mage::getConfig()->saveConfig('shiphawk/order/status', 0);
        }

    }
}
