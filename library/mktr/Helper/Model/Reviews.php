<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper\Model;

use Mktr\Helper\Core;

class Reviews
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
        return 'review';
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
    private static $review = array(
        'author' => '',
        'customer_id' => 0,
        'product_id' => '',
        'text' => '',
        'rating' => 0,
        'date_added' => '',
        'status' => 1
    );

    public static function addReview($data) {
        $data = array_merge(self::$review, $data);

        Core::query("INSERT INTO `". DB_PREFIX . self::getTable() ."` SET".
        " `author` = '". Core::escape($data['author']) ."',".
        " `customer_id` = '".(int)$data['customer_id'] ."',".
        " `product_id` = '". (int)$data['product_id'] ."',".
        " `text` = '". Core::escape($data['text']) ."',".
        " `rating` = '". (int)$data['rating'] ."',".
        " `status` = '". (int)$data['status'] ."',".
        " `date_added` = '". Core::escape($data['date_added']) ."'");

        self::$asset = Core::lastId();

        return self::$asset;
    }

}