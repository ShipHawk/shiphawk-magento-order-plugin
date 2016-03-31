<?php

class Shiphawk_Order_Model_Observer_Order
{
    protected function isAvailable() {
        return Mage::getStoreConfig('shiphawk/order/active') == 1;
    }

    public function push($observer)
    {
        if ($this->isAvailable()) {
            Mage::getSingleton('shiphawk_order/command_sendOrder')->execute($observer->getOrder());
        }
    }

    public function changeStatus($observer)
    {
        if ($this->isAvailable()) {
            Mage::getModel('shiphawk_order/command_changeStatus')->execute($observer->getOrder());
        }
    }
}
