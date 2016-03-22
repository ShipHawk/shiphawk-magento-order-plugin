<?php

class Shiphawk_Order_Model_Observer_Config
{
    public function check($observer)
    {
        Mage::getSingleton('shiphawk_order/command_checkConfiguration')->execute();
    }
}
