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
            ['lt' => strtotime('now - 14 days')]
        )->load();

        foreach ($orders as $order) {
            Mage::getSingleton('shiphawk_order/sendOrderCommand')->execute($order);
        }
        Mage::getConfig()->saveConfig($key, 1);
    }
}
