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
    
    public static function removeNonCharacters($string) {
        return preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $string);
    }

    public static function addReview($data) {
        $data = array_merge(self::$review, $data);
        foreach ($data as $key=>$value) {
            switch ($key) {
                case 'customer_id':
                case 'product_id':
                case 'rating':
                case 'status':
                    $data[$key] = (int) $value;
                break;
                default:
                    $data[$key] = Core::escape((string) $value);
            }   
        }
        
        $data['text'] = self::removeNonCharacters($data['text']);
        
        $query = "SELECT `review_id`
          FROM `" . DB_PREFIX . self::getTable() . "` 
          WHERE `author` = '" .$data['author'] . "'
          AND `customer_id` = '" . $data['customer_id'] . "'
          AND `product_id` = '" . $data['product_id'] . "'
          AND `text` = '" . $data['text'] . "'
          AND `rating` = '" . $data['rating'] . "' "
          // ."AND `date_added` = '" . $data['date_added'] . "' "
          ."LIMIT 1;";

        $row = Core::query($query);
        
        if (empty($row->row)) {
            Core::query("INSERT INTO `" . DB_PREFIX . self::getTable() . "` SET" .
            " `author` = '" . $data['author'] . "'," .
            " `customer_id` = '" . $data['customer_id'] . "'," .
            " `product_id` = '" . $data['product_id'] . "'," .
            " `text` = '" . $data['text'] . "'," .
            " `rating` = '" . $data['rating'] . "'," .
            " `status` = '" . $data['status'] . "'," .
            " `date_added` = '" . $data['date_added'] . "'");
    
            self::$asset = Core::lastId();
        } else {
            self::$asset = $row->row['review_id'];
        }

        return self::$asset;
    }

}
