<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Api;
use Mktr\Helper\Config;
use Mktr\Helper\Core;
use Mktr\Helper\Data;
use Mktr\Helper\Model\Customer;
use Mktr\Helper\Valid;

class Reviews
{
    private static $init = null;

    private static $map = array();

    public static function init()
    {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function get($f = 'fileName'){
        if (isset(self::$map[$f]))
        {
            return self::$map[$f];
        }
        return null;
    }

    public static function execute() {
        // Valid::getParam('start_date-type', date('Y-m-d'));
        $t = Valid::getParam('start_date', null);
        if ($t === null) {
            $t = strtotime("-1 day");
            // (new \DateTime())->modify('-1 day')->setTime(0, 0, 0);
        }

        $o = Api::send("product_reviews", array(
            't' => strtotime($t)
        ), false);
        if ($o->getStatus() == 200 && !empty($o->getContent())) {

            $xml = simplexml_load_string($o->getContent(), 'SimpleXMLElement', LIBXML_NOCDATA);
            $added = array();
            $revStore = Data::init()->{"reviewStore" . Config::getRestKey()};

            foreach ($xml->review as $value) {
                if (isset($value->review_date)) {
                    if (!isset($revStore[(string) $value->review_id])) {
                        $add = array(
                            'author'    => $value->review_author,
                            'product_id'=> $value->product_id, // <=== The product ID where the review will show up
                            'text'      => $value->review_text,
                            'rating' => round(((int) $value->rating / 2)),
                            'date_added'=> date('Y-m-d H:i:s')
                        );

                        $user = Customer::getByEmail($value->review_email);

                        if ($user) {
                            $add['customer_id'] = Customer::id();
                        }

                        $comment_id = \Mktr\Helper\Model\Reviews::addReview($add);
                        $added[(string) $value->review_id] = $comment_id;
                    } else {
                        $added[(string) $value->review_id] = $revStore[(string) $value->review_id];
                    }
                }
            }

            Data::init()->{"reviewStore" . Config::getRestKey()} = $added;

            Data::init()->save();

            return $xml;
        }

        return null;
    }
}
