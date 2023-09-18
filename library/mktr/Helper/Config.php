<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

use Mktr\Helper\Model\mkConfig;
use Mktr\Helper\Model\Store;

/**
 * @property $db
 * @property $controller
 *
 * @method static getStatus()
 * @method static getKey()
 * @method static getRestKey()
 * @method static getCustomerId()
 * @method static getOptIn()
 * @method static getPushStatus()
 * @method static getRefundStatus()
 * @method static getDefaultStock()
 * @method static getAllowExport()
 * @method static getBrandAttribute()
 * @method static getColorAttribute()
 * @method static getSizeAttribute()
 * @method static getCronFeed()
 * @method static getUpdateFeed()
 * @method static getCronReview()
 * @method static getUpdateReview()
 * @method static getSelectors()
 * @method static getTagCode()
 */

class Config
{
    private static $init = null;
    public static $code = 'mktr_tracker';
    public static $dateFormat = "Y-m-d H:i";
    public static $vSeparator = "-";

    const space = "\n        ";
    const defMime = 'xml';

    const FireBase = 'const firebaseConfig = {
    apiKey: "AIzaSyA3c9lHIzPIvUciUjp1U2sxoTuaahnXuHw",
    projectId: "themarketer-e5579",
    messagingSenderId: "125832801949",
    appId: "1:125832801949:web:0b14cfa2fd7ace8064ae74"
};

firebase.initializeApp(firebaseConfig);';

    const FireBaseMessaging = 'importScripts("https://www.gstatic.com/firebasejs/9.4.0/firebase-app-compat.js");
importScripts("https://www.gstatic.com/firebasejs/9.4.0/firebase-messaging-compat.js");
importScripts("./firebase-config.js");
importScripts("https://t.themarketer.com/firebase.js");';

    private static $discountRules = array(
        0 => "fixed_cart",
        1 => "percent",
        2 => "free_shipping"
    );

    public static $configDefaults = array(
        'status' => 0,
        'tracking_key' => '',
        'rest_key' => '',
        'customer_id'=>'',
        'cron_feed' => 0,
        'update_feed' => 4,
        'cron_review' => 0,
        'update_review' => 4,
        'opt_in' => 0,
        'push_status' => 0,
        'refund_status' => 11,
        'default_stock' => 0,
        'allow_export' => 0,
        'selectors' => "button[type='button']",
        'brand' => 'brand',
        'color' => 'color',
        'size' => 'size',
        'google_status' => 0,
        /* TODO Google Test */
        'google_tracking' => ""//'GTM-P3TT7N2'
    );

    private static $funcNames = array(
        'getStatus' => array('status', 'int'),
        'getKey' => array('tracking_key', false),
        'getRestKey' => array('rest_key', false),
        'getCustomerId' => array('customer_id', false),
        'getOptIn' => array('opt_in', 'int'),
        'getPushStatus' => array('push_status', 'int'),
        'getRefundStatus' => array('refund_status', 'int'),
        'getDefaultStock' => array('default_stock', 'int'),
        'getAllowExport' => array('allow_export', 'int'),
        'getSelectors' => array('selectors', false),
        'getBrandAttribute' => array('brand', false),
        'getColorAttribute' => array('color', false),
        'getSizeAttribute' => array('size', false),
        'getCronFeed' => array('cron_feed', 'int'),
        'getUpdateFeed' => array('update_feed', 'int'),
        'getCronReview' => array('cron_review', 'int'),
        'getUpdateReview' => array('update_review', 'int'),
        'getGoogleStatus' => array('google_status', 'int'),
        'getTagCode' => array('google_tracking', false),

    );

    private static $configNames = array(
        'status' => 'status',
        'tracking_key' => 'tracking_key',
        'rest_key' => 'rest_key',
        'customer_id'=>'customer_id',
        'cron_feed' => 'cron_feed',
        'update_feed' => 'update_feed',
        'cron_review' => 'cron_review',
        'update_review' => 'update_feed',
        'opt_in' => 'opt_in',
        'push_status' => 'push_status',
        'refund_status' => 'refund_status',
        'default_stock' => 'default_stock',
        'allow_export' => 'allow_export',
        'selectors' => 'selectors',
        'brand' => 'brand',
        'color' => 'color',
        'size' => 'size',
        'google_status' => 'google_status',
        'google_tracking' => 'google_tracking'
    );

    private static $configValues = array();

    private $data = array();
    private $save = false;

    public static $addToModule = false;

    public function __construct() {
        $store = Store::getStores(Core::getStoreID());

        $data = Core::cache('mktr_settings_' . Core::getStoreID());

        if (!$data) {
            $data = mkConfig::getSettings();

            $data['store_id'] = Core::getStoreID();
            $data['store_name'] = $store['name'];
        }

        if (Core::getOcVersion() >= "4") {
            self::$configDefaults['selectors'] = "button[type='submit']";
        }

        $data = array_merge(self::$configDefaults, $data);

        foreach ($data  as $key => $item) {
            $this->data[$key] = $item;
        }

        self::$init = $this;
    }

    public static function __callStatic($name, $arguments)
    {
        return self::callNow($name);
    }

    public function __call($name, $arguments)
    {
        return self::callNow($name);
    }

    private static function callNow($name)
    {
        $func = self::$funcNames;

        if (isset($func[$name]))
        {
            switch ($func[$name][1]) {
                case 'int':
                    return (int) self::getValue($func[$name][0]);
                default:
                    return self::getValue($func[$name][0]);
            }
        }
        return null;
    }

    public static function getValue($name = null)
    {
        if (array_key_exists($name, self::$configValues)) {
            return self::$configValues[$name];
        }

        if (array_key_exists($name, Config::$configNames))
        {
            self::$configValues[$name] = self::init()->get(Config::$configNames[$name]);

            if (in_array($name, array('color', 'size', 'brand'))) {
                $exp = array();

                foreach (explode("|", self::$configValues[$name]) as $v) {
                    $exp[strtolower($v)] = $v;
                }

                self::$configValues[$name] = $exp;
            }

            return self::$configValues[$name];
        }

        return null;
    }

    public function get($key) {
        return (isset($this->data[$key]) ? $this->data[$key] : null);
    }

    public function set($key, $value) {
        $this->save = true;
        $this->data[$key] = $value;
        return self::$init;
    }

    public function save() {
        if ($this->save) {
            $id = Core::getStoreID();

            $toSave = array();

            foreach ($this->data as $key => $item) {
                $toSave[$key] = $item;
            }

            mkConfig::editSetting($toSave, $id);

            $this->save = false;

            Core::setCache('mktr_settings_' . Core::getStoreID(), $toSave);
        }
        return self::$init;
    }

    public function has($key) {
        return isset($this->data[$key]);
    }

    /** @noinspection PhpUnused */
    public function getAll() {
        return $this->data;
    }

    public static function init() {
        if (self::$init === null) {
            new self();
        }

        return self::$init;
    }

    /** @noinspection PhpUnused */
    public static function getDefault($key) {
        return isset(self::$configDefaults[$key]) ? self::$configDefaults[$key] : null;
    }

    /** @noinspection PhpUnused */
    public static function getDiscountRules($get = null)
    {
        if (is_null($get)) {
            return self::$discountRules;
        }

        $check = self::$discountRules;

        if (isset($check[$get]))
        {
            return self::$discountRules[$get];
        }
        return null;
    }

    public static function getBaseURL()
    {
        return  Core::i()->getBaseUrl . '/';
    }

    /** @noinspection PhpUnused */
    public static function getFireBase()
    {
        return self::FireBase;
    }

    /** @noinspection PhpUnused */
    public static function getFireBaseMessaging()
    {
        return self::FireBaseMessaging;
    }
}
