<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper\Model;

use Mktr\Helper\Core;

/**
 * @method static status()
 * @method static email()
 * @method static telephone()
 * @method static lastname()
 * @method static firstname()
 * @method static id()
 */
class Customer
{
    private static $asset = null;

    private static $init = null;

    const STATUS_SUBSCRIBED = 1;

    private static $map = array(
        'status' => 'newsletter',
        'id' => 'customer_id'
    );

    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }

        return self::$init;
    }

    public static function getTable() {
        return 'customer';
    }

    public static function __callStatic($name, $arguments) {
        return self::getValue($name);
    }

    public function __call($name, $arguments) {
        return self::getValue($name);
    }

    public static function getValue($name) {
        $name = isset(self::$map[$name]) ? self::$map[$name] : $name;

        if (isset(self::$asset[$name])) {
            return self::$asset[$name];
        }

        if (empty(self::$asset) && isset(Core::request()->post[$name])) {
            return Core::request()->post[$name];
        }

        return null;
    }

    public static function getByEmail($email) {
        self::$asset = Core::query("SELECT * FROM `". DB_PREFIX.self::getTable() ."`  WHERE `email` = '".$email."' LIMIT 1")->row;
        return self::init();
    }
}