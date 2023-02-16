<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Core;
use Mktr\Helper\Valid;

class clearEvents
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

        $eventData = Core::getSessionData("ClearMktr");

        if (!empty($eventData)) {
            foreach ($eventData as $key => $value) {
                $eventData1 = Core::getSessionData($key);

                foreach ($value as $value1) {
                    unset($eventData1[$value1]);
                }

                Core::setSessionData($key, $eventData1);
            }
            Core::setSessionData("ClearMktr", array());
        }

        return "console.log(2);";
    }
}
