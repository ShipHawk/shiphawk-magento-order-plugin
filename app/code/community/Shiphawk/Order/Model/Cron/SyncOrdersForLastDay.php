<?php
class Shiphawk_Order_Model_Cron_SyncOrdersForLastDay
{
    public function run()
    {
        Mage::log('Started ShipHawk Orders Sync job', Zend_Log::INFO, 'shiphawk_orders_sync.log', true);

        $perPage = 50;
        $ordersCount = Mage::getSingleton('sales/order')->getCollection()->addAttributeToFilter(
                        'created_at',
                        ['gt' => date("Y-m-d", strtotime('now - 1 days'))]
                    )->addAttributeToFilter(
                        'shiphawk_order_id', array('null' => true)
                    )->getSize();


        Mage::log('OrdersCount: ' . var_export($ordersCount, true), Zend_Log::INFO, 'shiphawk_orders_sync.log', true);

        $pages = ceil($ordersCount/$perPage);
        for ($page = 1; $page <= $pages; $page++){
            Mage::log('Page #' . $page, Zend_Log::INFO, 'shiphawk_orders_sync.log', true);

            $orders = Mage::getSingleton('sales/order')->getCollection()->addAttributeToFilter(
                        'created_at',
                        ['gt' => date("Y-m-d", strtotime('now - 1 days'))]
                    )->addAttributeToFilter(
                        'shiphawk_order_id', array('null' => true)
                    )->setPageSize($perPage)->setCurPage($page);

            foreach ($orders as $order) {
                Mage::log('Sync order #' . $order->getIncrementId(), Zend_Log::INFO, 'shiphawk_orders_sync.log', true);
                Mage::getSingleton('shiphawk_order/command_sendOrder')->execute($order);
                $order->save();
            }
        }
    }
}
