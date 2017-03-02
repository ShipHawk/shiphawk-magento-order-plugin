<?php
/**
 * ShipHawk freemethod source implementation
 */
class ShipHawk_MyCarrier_Model_FreeMethod
    extends Mage_Usa_Model_Shipping_Carrier_Fedex_Source_Method
{
    public function toOptionArray()
   {
        return array(
            array('label' => 'FedEx 2 Day',                                                   'value' => 'FedEx 2 Day'),
            array('label' => 'FedEx 2 Day Am',                                                'value' => 'FedEx 2 Day Am'),
            array('label' => 'FedEx Express Saver',                                           'value' => 'FedEx Express Saver'),
            array('label' => 'FedEx First Overnight',                                         'value' => 'FedEx First Overnight'),
            array('label' => 'FedEx First Overnight Saturday Delivery',                       'value' => 'FedEx First Overnight Saturday Delivery'),
            array('label' => 'FedEx Ground',                                                  'value' => 'FedEx Ground'),
            array('label' => 'FedEx Ground Home Delivery',                                    'value' => 'FedEx Ground Home Delivery'),
            array('label' => 'FedEx International Economy',                                   'value' => 'FedEx International Economy'),
            array('label' => 'FedEx International First',                                     'value' => 'FedEx International First'),
            array('label' => 'FedEx International Ground',                                    'value' => 'FedEx International Ground'),
            array('label' => 'FedEx International Priority',                                  'value' => 'FedEx International Priority'),
            array('label' => 'FedEx Priority Overnight',                                      'value' => 'FedEx Priority Overnight'),
            array('label' => 'FedEx Priority Overnight Saturday Delivery',                    'value' => 'FedEx Priority Overnight Saturday Delivery'),
            array('label' => 'FedEx Standard Overnight',                                      'value' => 'FedEx Standard Overnight'),
            array('label' => 'First Class Mail International',                                'value' => 'First Class Mail International'),
            array('label' => 'First Class Package International Service',                     'value' => 'First Class Package International Service'),
            array('label' => 'First-Class Mail',                                              'value' => 'First-Class Mail'),
            array('label' => 'Global Express Guaranteed',                                     'value' => 'Global Express Guaranteed'),
            array('label' => 'Library Mail',                                                  'value' => 'Library Mail'),
            array('label' => 'Media Mail',                                                    'value' => 'Media Mail'),
            array('label' => 'Parcel Select Ground',                                          'value' => 'Parcel Select Ground'),
            array('label' => 'Priority Mail',                                                 'value' => 'Priority Mail'),
            array('label' => 'Priority Mail Express',                                         'value' => 'Priority Mail Express'),
            array('label' => 'Priority Mail Express Flat Rate Envelope',                      'value' => 'Priority Mail Express Flat Rate Envelope'),
            array('label' => 'Priority Mail Express Flat Rate Legal Envelope',                'value' => 'Priority Mail Express Flat Rate Legal Envelope'),
            array('label' => 'Priority Mail Express International',                           'value' => 'Priority Mail Express International'),
            array('label' => 'Priority Mail Express International Flat Rate Envelope',        'value' => 'Priority Mail Express International Flat Rate Envelope'),
            array('label' => 'Priority Mail Express International Flat Rate Legal Envelope',  'value' => 'Priority Mail Express International Flat Rate Legal Envelope'),
            array('label' => 'Priority Mail Express International Flat Rate Padded Envelope', 'value' => 'Priority Mail Express International Flat Rate Padded Envelope'),
            array('label' => 'Priority Mail Flat Rate Envelope',                              'value' => 'Priority Mail Flat Rate Envelope'),
            array('label' => 'Priority Mail Flat Rate Legal Envelope',                        'value' => 'Priority Mail Flat Rate Legal Envelope'),
            array('label' => 'Priority Mail International',                                   'value' => 'Priority Mail International'),
            array('label' => 'Priority Mail International Flat Rate Envelope',                'value' => 'Priority Mail International Flat Rate Envelope'),
            array('label' => 'Priority Mail International Flat Rate Legal Envelope',          'value' => 'Priority Mail International Flat Rate Legal Envelope'),
            array('label' => 'Priority Mail International Flat Rate Padded Envelope',         'value' => 'Priority Mail International Flat Rate Padded Envelope'),
            array('label' => 'Priority Mail International Large Flat Rate Box',               'value' => 'Priority Mail International Large Flat Rate Box'),
            array('label' => 'Priority Mail International Medium Flat Rate Box',              'value' => 'Priority Mail International Medium Flat Rate Box'),
            array('label' => 'Priority Mail International Small Flat Rate Box',               'value' => 'Priority Mail International Small Flat Rate Box'),
            array('label' => 'Priority Mail Large Flat Rate Box',                             'value' => 'Priority Mail Large Flat Rate Box'),
            array('label' => 'Priority Mail Medium Flat Rate Box',                            'value' => 'Priority Mail Medium Flat Rate Box'),
            array('label' => 'Priority Mail Small Flat Rate Box',                             'value' => 'Priority Mail Small Flat Rate Box'),
            array('label' => 'UPS Ground',                                                    'value' => 'UPS Ground'),
            array('label' => 'UPS Next Day Air',                                              'value' => 'UPS Next Day Air'),
            array('label' => 'UPS Next Day Air Early',                                        'value' => 'UPS Next Day Air Early'),
            array('label' => 'UPS Next Day Air Saver',                                        'value' => 'UPS Next Day Air Saver'),
            array('label' => 'UPS Second Day Air',                                            'value' => 'UPS Second Day Air'),
            array('label' => 'UPS Second Day Air A.M.',                                       'value' => 'UPS Second Day Air A.M.'),
            array('label' => 'UPS Standard',                                                  'value' => 'UPS Standard'),
            array('label' => 'UPS SurePost',                                                  'value' => 'UPS SurePost'),
            array('label' => 'UPS Three-Day Select',                                          'value' => 'UPS Three-Day Select'),
            array('label' => 'UPS Worldwide Expedited',                                       'value' => 'UPS Worldwide Expedited'),
            array('label' => 'UPS Worldwide Express',                                         'value' => 'UPS Worldwide Express'),
            array('label' => 'UPS Worldwide Express Freight',                                 'value' => 'UPS Worldwide Express Freight'),
            array('label' => 'UPS Worldwide Express Plus',                                    'value' => 'UPS Worldwide Express Plus'),
            array('label' => 'UPS Worldwide Saver',                                           'value' => 'UPS Worldwide Saver')
        );
    }
}
