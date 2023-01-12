<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

/**
 * @method static is_home()
 */
class CheckPage
{
    private static $current_page = null;

    private static $pages = array(
        'is_home' => array(
            'common/home',
            ''
        ),
        'is_product_category' => array(
            'product/category'
        ),
        'is_product' => array(
            'product/product'
        ),
        'is_brand' => array(
            'product/manufacturer/info'
        ),
        'is_checkout' => array(
            'checkout/checkout',
            'checkout/simplecheckout',
            'checkout/ajaxquickcheckout',
            'checkout/ajaxcheckout',
            'checkout/quickcheckout',
            'checkout/onepagecheckout',
            'supercheckout/supercheckout',
            'quickcheckout/cart',
            'quickcheckout/checkout'
        ),
        'is_search' => array(
            'product/search'
        )
    );

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
        return in_array(self::current_page(), self::$pages[$name]);
    }

    public static function current_page() {
        if (self::$current_page === null) {
            self::$current_page = isset(Core::request()->get['route']) ? Core::request()->get['route'] : '';
        }
        return self::$current_page;
    }

}