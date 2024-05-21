<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Model;

use Mktr\Helper\Config;
use Mktr\Helper\Core;
use Mktr\Helper\Valid;

/**
 * @method static id()
 * @method static order_status()
 * @method static order_status_id()
 * @method static date_added()
 * @method static created_at()
 * @method static order_firstname()
 * @method static payment_firstname()
 * @method static shipping_firstname()
 * @method static order_lastname()
 * @method static payment_lastname()
 * @method static shipping_lastname()
 * @method static firstname()
 * @method static shipping_city()
 * @method static payment_city()
 * @method static city()
 * @method static county()
 * @method static shipping_country()
 * @method static payment_country()
 * @method static shipping_address_1()
 * @method static payment_address_1()
 * @method static payment_address_2()
 * @method static shipping_address_2()
 * @method static address()
 * @method static voucher_code()
 * @method static coupon_code()
 * @method static lastname()
 * @method static total_shipping()
 * @method static shipping()
 * @method static total_total()
 * @method static total_tax()
 * @method static total_coupon()
 * @method static total_voucher()
 * @method static products()
 * @method static total_value()
 * @method static extra_products()
 * @method static email()
 * @method static telephone()
 * @method static discount_value()
 * @method static discount_code()
 * @method static tax()
 */
class Order
{
    private static $init = null;
    public static $asset = null;

    private static $data = array();

    private static $statusList = null;

    private static $loadTotal = false;

    private static $ordersArgs = array(
        'limit' => 250,
        'page' => 1,
        'start_date' => '1900-01-01'
    );

    private static $orders = null;

    /* "status" */
    private static $valueNames = array(
        'order_status'  => 'getStatus',
        'refund_value'  => 'getRefund',
        // 'email_address' => 'getEmail',
        'create_at'     => 'getCreateAt',
        'firstname'     => 'getFirstName',
        'lastname'      => 'getLastName',
        'city'          => 'getCity',
        'county'        => 'getCountry',
        'address'       => 'getAddress',
        'discount_value'=> 'getDiscount',
        'discount_code' => 'getDiscountCode',
        'shipping'      => 'getShipping',
        'tax'           => 'getTax',
        'total_value'   => 'getTotal',
        'products'      => 'getProducts',
        'extra_products'=> 'getProductsFeed'
    );

    private static $assetNames = array(
        'id' => 'order_id',
        'order_firstname' => 'firstname',
        'order_lastname' => 'lastname',
        'voucher' => 'total_voucher',
        'coupon' => 'total_coupon'
    );

    private static $extraValue = array(
        "order_no" => "id",
        "order_status" => "getStatus",
        "refund_value" => "getRefund",
        "created_at" => "create_at",
        "email_address" => "email",
        "phone" => "telephone",
        "firstname" => "firstname",
        "lastname" => "firstname",
        "city" => "city",
        "county" => "county",
        "address" => "address",
        "discount_value" => "discount_value",
        "discount_code" => "discount_code",
        "shipping" => "shipping",
        "tax" => "tax",
        "total_value" => "total_value",
        "products" => "getProductsFeed",
    );

    public static function init()
    {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function __callStatic($name, $arguments)
    {
        return self::getValue($name);
    }

    public function __call($name, $arguments)
    {
        return self::getValue($name);
    }

    public static function getValue($name)
    {
        if (isset(self::$data[$name])) {
            return self::$data[$name];
        }

        if (self::$asset == null){
            self::getById();
        }

        self::$data[$name] = null;

        if (isset(self::$asset[$name])) {
            self::$data[$name] = self::$asset[$name];
        }

        if (isset(self::$assetNames[$name])) {
            $v = self::$assetNames[$name];
            self::$data[$name] = self::$asset[$v];
        }

        if (isset(self::$valueNames[$name])) {
            $v = self::$valueNames[$name];
            self::$data[$name] = self::{$v}();
        }

        return self::$data[$name];
    }

    public static function getById($id = null)
    {
        if ($id === null && isset(Core::session()->data['order_id'])) {
            $id = Core::session()->data['order_id'];
        }

        if ($id !== null) {
            self::$data = array();
            self::$asset = Core::query("SELECT * FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int) $id . "' LIMIT 1")->row;
        }

        return self::init();
    }

    public static function getOrders($arg = array()) {
        self::$ordersArgs = array_merge(self::$ordersArgs, $arg);

        $start_date = self::$ordersArgs['start_date'];
        $page = self::$ordersArgs['page'];
        $limit = self::$ordersArgs['limit'];

        $offset = (($page - 1) * $limit);

        self::$orders = Core::query("SELECT * FROM `" . DB_PREFIX . "order` WHERE `date_added` >= '" . $start_date .
            "' ORDER BY `order_id` LIMIT " . $limit .
            " OFFSET " . $offset);

        return self::$orders;
    }

    public static function selectOrder($s) {
        self::$data = array();
        self::$asset = self::$orders->rows[$s];

        return self::init();
    }
    public static function getFromStatusList($id) {
        if (self::$statusList === null) {
            $list = Core::query("SELECT * FROM `" . DB_PREFIX . "order_status` WHERE `language_id` = '1'");

            if ($list->num_rows == 0) {
                $list = Core::query("SELECT * FROM `" . DB_PREFIX . "order_status` WHERE `language_id` = '" . Core::ocConfig('config_language_id') . "'");
            }

            self::$statusList = array();

            foreach ($list->rows as $v) {
                self::$statusList[$v['order_status_id']] = $v['name'];
            }
        }

        if (!isset(self::$statusList[$id])) {
            self::$statusList[$id] = 'unknown';
        }

        return self::$statusList[$id];
    }

    public static function getStatus() {
        return self::getFromStatusList(self::order_status_id());
    }

    public static function getRefund()
    {
        $refund = 0;
        if (self::order_status_id() == Config::getRefundStatus()) {
            $refund = self::total_value();
        }

        return $refund;
    }

    public static function getCreateAt() {
        return Valid::correctDate(self::date_added());
    }

    public static function getDiscount()
    {
        self::getAllTotal();
        $data = '';
        if (self::total_coupon() !== null) {
            $data = substr(self::total_coupon(), 1);
        } else if (self::total_voucher() !== null) {
            $data = substr(self::total_voucher(), 1);
        }

        if (empty($data)) {
            $data = 0;
        }

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $data;
    }

    public static function getDiscountCode()
    {
        self::getAllTotal();
        $data = self::coupon_code();
        if (empty($data)) {
            $data = self::voucher_code();
        }

        if (empty($data)) {
            $data = '';
        }

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $data;
    }

    public static function getShipping()
    {
        self::getAllTotal();

        $data = 0;

        if (self::total_shipping() !== null) {
            $data = self::total_shipping();
        }

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $data;
    }

    public static function getTotal() {
        self::getAllTotal();

        $data = 0;

        if (self::total_total() !== null) {
            $data = self::total_total();
        }

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $data;
    }

    public static function getTax() {
        self::getAllTotal();

        $data = 0;

        $tax = self::total_tax();

        if (!empty($tax)) {
            $data = self::total_tax();
        }

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $data;
    }

    public static function getAllTotal() {
        if (!self::$loadTotal) {
            $total = Core::query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int) self::id() . "'")->rows;

            foreach ($total as $v) {

                if ($v['code'] === 'voucher' || $v['code'] === 'coupon') {
                    $matches = array();
                    preg_match('/\((.*)?\)/', $v['title'], $matches, PREG_OFFSET_CAPTURE);
                    self::$data[$v['code'] . '_code'] = $matches[1][0];
                }

                self::$data['total_' . $v['code']] = Core::digit2($v['value']);
            }
        }
    }

    public static function getProducts($feed = false) {
        $products = array();

        $pro = Core::query("SELECT * FROM `" . DB_PREFIX . "order_product` WHERE `order_id` = '" . (int) self::id() . "'")->rows;

        foreach ($pro as $p) {
            $opt = Core::query("SELECT * FROM `" . DB_PREFIX . "order_option` WHERE `order_id` = '" . (int) self::id() . "' AND `order_product_id` = '" . $p['order_product_id'] . "'")->rows;
            $id = array($p['product_id']);
            $sku = array($p['product_id']);
            foreach ($opt as $o) {
                if ($o['product_option_value_id']!=0) {
                    $id[] = $o['product_option_id'];
                    $id[] = $o['product_option_value_id'];
                    // $sku[] = $o['name'];
                    $sku[] = $o['product_option_id'];
                    $sku[] = $o['value'];
                }
            }
            $id = implode(Config::$vSeparator, $id);
            $sku = implode(Config::$vSeparator, $sku);
            
            $sku = str_replace(' ', '_', $sku);
            if (!empty($p['tax'])) {
                $p['price'] = $p['price'] + $p['tax'];
            }

            if (!$feed) {
                $send = array(
                    "product_id" => $p['product_id'],
                    "price" => Core::digit2($p['price']),
                    "quantity" => $p['quantity'],
                    "variation_sku" => $sku
                );
            } else {
                Product::getById($p['product_id']);
                $send = array(
                    "product_id" => Product::id(),
                    "name" => $p['name'],
                    "url" => Product::getUrl(),
                    "main_image" => Product::main_image(),
                    "category" => Product::category(),
                    "brand" => Product::brand(),
                    "price" => Core::digit2(Product::price()),
                    "sale_price" => Core::digit2($p['price']),
                    "quantity" => $p['quantity'],
                    "variation_id" => $id,
                    "variation_sku" => $sku
                );
            }

            $products[] = $send;
        }
        return $products;
    }

    public static function getProductsFeed() {
        return self::getProducts(true);
    }

    public static function getFirstName() {
        $fName = self::order_firstname();
        if (empty($fName)) {
            $fName = self::payment_firstname();
        }
        if (empty($fName)) {
            $fName = self::shipping_firstname();
        }
        if (empty($fName)) {
            $fName = "";
        }
        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $fName;
    }

    public static function getLastName() {
        $lName = self::order_lastname();

        if (empty($lName)) {
            $lName = self::payment_lastname();
        }

        if (empty($lName)) {
            $lName = self::shipping_lastname();
        }

        if (empty($lName)) {
            $lName = '';
        }

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $lName;
    }

    public static function getCity() {
        $data = self::payment_city();
        if (empty($data)) {
            $data = self::shipping_city();
        }
        if (empty($data)) {
            $data = "";
        }
        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $data;
    }

    public static function getCountry() {
        $data = self::payment_country();
        if (empty($data)) {
            $data = self::shipping_country();
        }
        if (empty($data)) {
            $data = "";
        }
        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $data;
    }

    public static function getAddress() {
        $add = array();
        if (self::getAddress1() !== null) {
            $add[] = self::getAddress1();
        }
        if (self::getAddress2() !== null) {
            $add[] = self::getAddress2();
        }
        return implode(' ', $add);
    }

    public static function getAddress1() {
        $data = self::payment_address_1();
        if (empty($data)) {
            $data = self::shipping_address_1();
        }
        if (empty($data)) {
            $data = "";
        }
        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $data;
    }

    public static function getAddress2() {
        $data = self::payment_address_2();

        if (empty($data)) {
            $data = self::shipping_address_2();
        }
        if (empty($data)) {
            $data = "";
        }

        /** @noinspection PhpExpressionAlwaysNullInspection */
        return $data;
    }

    public static function toArray()
    {
        $data = array();
        foreach (self::$valueNames as $key=>$value)
        {
            $data[$key] = self::$value();
        }

        return $data;
    }

    public static function AllArray()
    {
        $data = array();
        // self::$data
        foreach (self::$asset as $key=>$value)
        {
            $data[$key] = $value;
        }

        return $data;
    }

    public static function AllDataArray()
    {
        $data = array();
        // self::$data
        foreach (self::$data as $key=>$value) {
            $data[$key] = $value;
        }

        return $data;
    }


    public static function toExtraArray()
    {
        $data = array();

        foreach (self::$extraValue as $key=>$value) {
            $data[$key] = self::$value();
        }

        return $data;
    }
    
    public static function toApi()
    {
        return array(
            "number" => Order::id(),
            "email_address" => Order::email(),
            "phone" => Order::telephone(),
            "firstname" => Order::firstname(),
            "lastname" => Order::lastname(),
            "city" => Order::city(),
            "county" => Order::county(),
            "address" => Order::address(),
            "discount_value" => Order::discount_value(),
            "discount_code" => Order::discount_code(),
            "shipping" => Order::shipping(),
            "tax" => Order::tax(),
            "total_value" => Order::total_value(),
            "products" => Order::products(),
        );
    }
}
