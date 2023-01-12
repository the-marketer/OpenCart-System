<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper\Model;

use Mktr\Helper\Core;

class Coupon
{
    private static $asset = null;

    private static $init = null;

    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }

        return self::$init;
    }

    public static function getTable() {
        return 'coupon';
    }

    public static function __callStatic($name, $arguments) {
        return self::getValue($name);
    }

    public function __call($name, $arguments) {
        return self::getValue($name);
    }

    public static function getValue($name) {
        if (isset(self::$asset[$name])) {
            return self::$asset[$name];
        }

        return null;
    }
    private static $coupon = array(
        'name' => 'Discount Code MKTR',
        'code' => 'MKTR-1',
        'discount' => '1',
        'type' => 'P', /* P | F */
        'total' => 0,
        'logged' => 0,
        'shipping' => 0, /* 0|1 */
        'date_start' => '',
        'date_end' => '',
        'uses_total' => 1,
        'uses_customer' => 1,
        'status' => 1
    );

    public static function addCoupon($data) {
        $data = array_merge(self::$coupon, $data);

        Core::query(
            "INSERT INTO `" . DB_PREFIX . self::getTable() ."` SET".
            " `name` = '" . Core::escape($data['name']) . "',".
            " `code` = '" . Core::escape($data['code']) . "',".
            " `discount` = '" . (float)$data['discount'] . "',".
            " `type` = '" . Core::escape($data['type']) . "',".
            " `total` = '" . (float)$data['total'] . "',".
            " `logged` = '" . (int)$data['logged'] . "',".
            " `shipping` = '" . (int)$data['shipping'] . "',".
            " `date_start` = '" . Core::escape($data['date_start']) . "',".
            " `date_end` = '" . Core::escape($data['date_end']) . "',".
            " `uses_total` = '" . (int)$data['uses_total'] . "',".
            " `uses_customer` = '" . (int)$data['uses_customer'] . "',".
            " `status` = '" . (int)$data['status'] . "',".
            " `date_added` = NOW()");

        self::$asset = Core::lastId();

        return self::$asset;
    }

    public static function checkCode($code) {
        $q = Core::query(
            "SELECT DISTINCT * FROM ".DB_PREFIX.self::getTable()." WHERE".
            " code = '" . Core::escape($code) . "' LIMIT 1");
        return !empty($q->num_rows);
    }
}