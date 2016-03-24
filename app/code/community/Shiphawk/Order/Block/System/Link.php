<?php

class Shiphawk_Order_Block_System_Link extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    public function render(Varien_Data_Form_Element_Abstract $element)
    {
        return "<a href=\"{$element->getLink()}\">{$element->getLabel()}</a>";
    }
}
