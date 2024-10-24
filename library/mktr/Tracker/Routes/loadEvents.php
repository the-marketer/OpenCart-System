<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Core;
use Mktr\Helper\Config;
use Mktr\Tracker\Events;
use Mktr\Helper\Valid;

class loadEvents
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

        $lines = array();

        foreach (Events::$observerGetEvents as $event=>$Name)
        {
            if (!$Name[0]) {
                $eventData = Core::getSessionData($event);
                if (!empty($eventData))
                {
                    foreach ($eventData as $value)
                    {
                        $lines[] = "dataLayer.push(" . Events::getEvent($Name[1], $value)->toJson() . ");";
                    }
                }
                Core::setSessionData($event, array());
            }
        }

        $eventData = Core::getSessionData("setEmail");

        if (!empty($eventData)) {
            foreach ($eventData as $key => $value) { 
                if (filter_var($value['email_address'], FILTER_VALIDATE_EMAIL) !== false) {
                    $lines[] = "dataLayer.push(" . Events::getEvent("__sm__set_email", $value)->toJson() . ");"; 
                }
            }
            $linkVar =  Core::url()->link('mktr/api/', '', true);
            $linkVar =  $linkVar . (substr($linkVar, -1) === '/' ? '' : '/');

            $lines[] = '(function(){ let add = document.createElement("script"); add.async = true; add.src = "' . $linkVar .  'setEmail/"; add.src = add.src + (add.src.includes("?") ?  "&" : "?") + "mktr_time="+(new Date()).getTime(); let s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(add,s); })();';
        }
        
        $c = count($lines);
        $lines[] = 'console.log("Mktr","' . ( $c === 0 ? "No events to Load" : $c . " Events Loaded" ) . '");';
        
        return implode(Config::space, $lines);
    }
}
