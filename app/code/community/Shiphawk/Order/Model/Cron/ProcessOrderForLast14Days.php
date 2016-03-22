<?php

class Shiphawk_Order_Model_Cron_ProcessOrderForLast14Days
{
    public function run()
    {
        $key = preg_match('sendbox', Mage::getStoreConfig('shiphawk/order/gateway_url'))
            ? 'shiphawk/order/posecces_sendbox'
            : 'shiphawk/order/posecces_production';

        if (!Mage::getStoreConfigFlag('shiphawk/order/status') || Mage::getStoreConfigFlag($key)) {
            return;
        }

        $orders = Mage::getSingleton('sales/order')->getCollection()->addAttributeToFilter(
            'created_at',
            ['gt' => strtotime('now - 14 days')]
        );

        foreach ($orders as $order) {
            Mage::getSingleton('shiphawk_order/command_sendOrder')->execute($order);
        }
        Mage::getConfig()->saveConfig($key, 1);
        Mage::getConfig()->cleanCache();
    }
}
