<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Api;
use Mktr\Helper\Core;
use Mktr\Helper\Model\Customer;
use Mktr\Helper\Valid;
use Mktr\Tracker\Observer;

class setEmail
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

        $em = Core::getSessionData('setEmail');

        $allGood = true;

        $lines = array();

        foreach ($em as $val) {
            if (filter_var($val['email_address'], FILTER_VALIDATE_EMAIL) !== false) {
                if (isset($val['unsubscribe'])) {
                    if ($val['unsubscribe']) {
                        Api::send("remove_subscriber", array( "email" => $val['email_address'] ));
                    } else {
                        $send = array( "email" => $val['email_address'], "name" => explode("@", $val['email_address'])[0]);
                        Api::send("add_subscriber", $send);
                    }
                    if (Api::getStatus() != 200) { $allGood = false; }
                } else {
                    Customer::getByEmail($val['email_address']);
        
                    if (Customer::status() == Customer::STATUS_SUBSCRIBED) {
                        Api::send("add_subscriber", Observer::addSubscriber());
                        if (Api::getStatus() != 200) { $allGood = false; }
                    } else {
                        // Api::send("remove_subscriber", array( "email" => $val['email_address'] ));
                    }
                }
            }
            // $lines[] = "dataLayer.push(" . Events::getEvent('__sm__set_email', $val)->toJson() . ");";
            // if (Api::getStatus() != 200) { $allGood = false; }
        }

        if ($allGood)
        {
            Core::setSessionData('setPhone', array());
            Core::setSessionData('setEmail', array());
        }

        $lines[] = 'console.log(' . ($allGood ? 1 : 0 ) . ');';
        
        return implode(PHP_EOL, $lines);
    }
}
