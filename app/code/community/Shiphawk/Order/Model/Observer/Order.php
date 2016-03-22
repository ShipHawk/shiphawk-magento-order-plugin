<?php

class Shiphawk_Order_Model_Observer_Order
{
    public function push($observer)
    {
        if (Mage::getStoreConfig('shiphawk/order/active') != 1) {
            return;
        }

        Mage::getSingleton('shiphawk_order/command_sendOrder')->execute($observer->getOrder());
    }
}
