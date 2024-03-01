<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Core;
use Mktr\Helper\Model\Customer;
use Mktr\Tracker\Observer;
use Mktr\Helper\Api;
use Mktr\Helper\Valid;

class saveOrder
{
    private static $init = null;

    public static function init()
    {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }
    public static function execute()
    {
        Valid::setParam('mime-type', 'js');

        $Order = Core::getSessionData('saveOrder');

        $allGood = true;
        $list = array();

        if (!empty($Order)) {

            foreach ($Order as $orderId)
            {
                if (isset($orderId["number"])) {
                    $sOrder = $orderId;
                } else {
                    \Mktr\Tracker\Model\Order::getById($orderId);
                    $sOrder = \Mktr\Tracker\Model\Order::toApi();
                }

                Api::send("save_order", $sOrder);

                \Mktr\Helper\OrderLogs::i()->addToIfNot('orderIDs', $sOrder['number']);
                \Mktr\Helper\OrderLogs::i()->save();

                $list[] = $sOrder;
                if (Api::getStatus() != 200) {
                    $allGood = false;
                }

                if (!empty($sOrder['email_address'])) {
                    Customer::getByEmail($sOrder['email_address']);

                    if (Customer::status() == Customer::STATUS_SUBSCRIBED)
                    {
                        Api::send("add_subscriber", Observer::addSubscriber());
                        if (Api::getStatus() != 200) {
                            $allGood = false;
                        }
                    }
                }
            }

            if ($allGood)
            {
                Core::setSessionData('saveOrder', array());
            }
        }
        return 'console.log(' . ($allGood ? 1 : 0 ) . ', ' . json_encode($list ,true) . ');';
    }
}
