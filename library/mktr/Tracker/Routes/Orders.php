<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Core;
use Mktr\Helper\Valid;
use Mktr\Tracker\Model\Order;

class Orders
{
    private static $init = null;

    private static $map = array(
        "fileName" => "orders",
        "secondName" => "order"
    );

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
    /** @noinspection PhpExpressionAlwaysNullInspection */
    public static function execute(){
        $page = Valid::getParam('page');
        $stop = false;

        if ($page !== null) {
            $stop = true;
        }

        $args = array(
            'start_date' => Core::request()->get['start_date'],
            'page' => $page === null ? 1 : $page,
        );


        $get = array();

        do {
            $orders = Order::getOrders($args);

            if ($stop) {
                $pages = 0;
            } else {
                $pages = $orders->num_rows;
            }

            foreach ($orders->rows as $key => $val)
            {
                $get[] = Order::selectOrder($key)->toExtraArray();
            }

            $args['page']++;
        } while (0 < $pages);

        return $get;
    }
}