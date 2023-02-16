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

        foreach ($em as $val)
        {
            Customer::getByEmail($val['email_address']);

            if (Customer::status() == Customer::STATUS_SUBSCRIBED) {
                Api::send("add_subscriber", Observer::addSubscriber());
            } else {
                Api::send("remove_subscriber", array(
                    "email" => $val['email_address']
                ));
            }

            if (Api::getStatus() != 200) {
                $allGood = false;
            }
        }

        if ($allGood)
        {
            Core::setSessionData('setPhone', array());
            Core::setSessionData('setEmail', array());
        }

        return 'console.log('.(int)$allGood.');';
    }
}