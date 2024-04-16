<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker;

use Mktr\Helper\Api;
use Mktr\Helper\Core;
use Mktr\Helper\Config;
use Mktr\Helper\FileSystem;
use Mktr\Helper\Model\Customer;
use Mktr\Tracker\Model\Order;
use Mktr\Tracker\Model\Product;


class Observer
{
    private static $init = null;
    private static $eventName = null;
    private static $eventData = array();


    private static $routes = array(
        'checkout/cart/add' => 'addToCart',
        'checkout/cart/remove' => 'removeFromCart',
        'checkout/cart.add' => 'addToCart',
        'checkout/cart.remove' => 'removeFromCart',
        'account/register' => 'RegisterOrLogIn',
        'account/login' => 'RegisterOrLogIn',
        'checkout/success' => 'saveOrder',
        'extension/payment/cod/confirm' => 'saveOrder',
        'extension/payment/ipay/confirm' => 'saveOrder',
        'account/newsletter' => 'RegisterOrLogIn',
        'api/order/edit' => 'orderUp',
        'api/order/history' => 'orderUp',
        'sale/order/history' => 'orderUp',
        'sale/order|call' => 'orderUp',
        'api/order|edit' => 'orderUp',
        'checkout/cart|add' => 'addToCart',
        'checkout/cart|remove' => 'removeFromCart',
        
        'api/order.edit' => 'orderUp',
        'api/order.history' => 'orderUp',
        'sale/order.history' => 'orderUp',
        'sale/order.call' => 'orderUp',
        'checkout/cart.add' => 'addToCart',
        'checkout/cart.remove' => 'removeFromCart',

        'checkout/cart' => 'removeFromCart',
        'module/cart' => 'remove',
        'account/register|register' => 'RegisterOrLogIn',
        'account/login|login' => 'RegisterOrLogIn',
        'account/newsletter|save' => 'RegisterOrLogIn',

        'account/wishlist/add' => 'addToWishlist',
        'account/wishlist.add' => 'addToWishlist',
        'account/wishlist|add' => 'addToWishlist',
        'account/wishlist' => 'removeFromWishlist',
        'account/wishlist.remove' => 'removeFromWishlist',
        'account/wishlist|remove' => 'removeFromWishlist',
        'account/register.register' => 'RegisterOrLogIn',
        'account/login.login' => 'RegisterOrLogIn',
        'account/newsletter.save' => 'RegisterOrLogIn'
        /* 
        New EVENT 2
        */
    );

    private static $defPostAddRemove = array(
        'product_id' => null,
        'quantity' => 1,
        'option' => null
    );

    public static function init($route = null, $data = null)
    {
        if (self::$init == null) {
            self::$init = new self();
        }

        if ($route !== null) {
            if (isset(self::$routes[$route])) {
                switch ($route) {
                    /*
                    New EVENT 3
                    */
                    case 'checkout/cart|add':
                    case 'checkout/cart/add':
                    case 'checkout/cart.add':
                        $p = array_merge(self::$defPostAddRemove, Core::request()->post);

                        Product::getById($p['product_id']);

                        $variant = self::getVariantID($p['option']);

                        self::addToCart($p['product_id'], $p['quantity'], $variant);
                    break;
                    case 'checkout/cart':
                    case 'module/cart':
                        
                        if (isset(Core::request()->get['remove'])) {
                            $remove = Core::request()->get['remove'];
                            $product = explode(':', $remove);
                            $product_id = $product[0];
                            // Options
                            if (isset($product[1])) {
                                $options = unserialize(base64_decode($product[1]));
                            } else {
                                $options = null;
                            }

                            $p = array_merge(self::$defPostAddRemove, array(
                                'product_id' => $product_id,
                                'option' => $options
                            ));

                            if (isset(Core::session()->data) &&
                                array_key_exists( 'cart', Core::session()->data) &&
                                array_key_exists($remove, Core::session()->data['cart'])) {
                                $p['quantity'] = Core::session()->data['cart'][$remove];
                            }

                            Product::getById($p['product_id']);

                            $variant = self::getVariantID($p['option']);

                            self::removeFromCart($p['product_id'], $p['quantity'], $variant);

                        }
                        break;
                    case 'checkout/cart|remove':
                    case 'checkout/cart/remove':
                    case 'checkout/cart.remove':
                        $item = Product::getCartItem(Core::request()->post['key']);

                        /** @noinspection PhpComposerExtensionStubsInspection */
                        $item['option'] = json_decode($item['option'], true);

                        $p = array_merge(self::$defPostAddRemove, $item);

                        Product::getById($p['product_id']);

                        $variant = self::getVariantID($p['option']);

                        self::removeFromCart($p['product_id'], $p['quantity'], $variant);
                    break;
                    case 'account/wishlist|add':
                    case 'account/wishlist/add':
                    case 'account/wishlist.add':
                        Product::getById(Core::request()->post['product_id']);

                        self::addToWishlist(Core::request()->post['product_id']);
                    break;
                    case 'account/wishlist|remove':
                    case 'account/wishlist/remove':
                    case 'account/wishlist.remove':
                        Product::getById(Core::request()->post['product_id']);

                        self::removeFromWishlist(Core::request()->post['product_id']);
                        break;
                    case 'account/wishlist':
                        if (isset($_COOKIE['EAX'])) {
            var_dump(Core::request()->get['remove']);
            die();
                        }
                        if (isset(Core::request()->get['remove'])) {
                            Product::getById(Core::request()->get['remove']);

                            self::removeFromWishlist(Core::request()->get['remove']);
                        }
                    break;
                    case 'extension/payment/ipay/confirm':
                    case 'extension/payment/cod/confirm':
                    case 'checkout/success':
                        $orderID = null;
                        if (isset(Core::session()->data['order_id'])) {
                            $orderID = Core::session()->data['order_id'];
                            Core::setSessionData('mktr_order_id', []);
                        } else {
                            $order = Core::getSessionData('mktr_order_id');
                            if (!empty($order) && array_key_exists('id', $order)) {
                                $orderID = $order['id'];
                                Core::setSessionData('mktr_order_id', []);
                            }
                        }
                        if ($orderID !== null) {
                            self::saveOrder($orderID);
                        }
                    break;
                    case 'account/register|register':
                    case 'account/register/register':
                    case 'account/register.register':
                    case 'account/register':
                    case 'account/login/login':
                    case 'account/login|login':
                    case 'account/login.login':
                    case 'account/login':
                        if (isset(Core::request()->post['email'])) {
                            self::emailAndPhone(Core::request()->post['email']);
                        }
                    break;
                    case 'api/order|edit':
                    case 'api/order/edit':
                    case 'api/order.edit':
                    case 'sale/order/call':
                    case 'sale/order|call':
                    case 'sale/order.call':
                    case 'api/order/history':
                    case 'api/order|history':
                    case 'api/order.history':
                    case 'sale/order/history':
                    case 'sale/order|history':
                    case 'sale/order.history':
                        if (isset(Core::request()->post['order_status_id'])) {
                            $oId = Core::request()->get['order_id'];
                            $status = Order::getFromStatusList(Core::request()->post['order_status_id']);
                            self::orderUp($oId, $status);
                        }
                    break;
                    case 'account/newsletter/save':
                    case 'account/newsletter|save':
                    case 'account/newsletter.save':
                    case 'account/newsletter':
                        if (isset(Core::request()->post['newsletter'])) {
                            self::emailAndPhone(Core::customer()->getEmail());
                        }
                    break;

                    default:
                        //Core::dd(Core::session()->data);
                }
            }
        }
        return self::$init;
    }

    public static function getVariantID($o) {

        if (Core::getOcVersion() >= "4") {
            $productOptions = Core::ocModel('catalog/product')->getOptions(Product::id());
        } else {
            $productOptions = Core::ocModel('catalog/product')->getProductOptions(Product::id());
        }
        if (!empty($productOptions)) {
            $add = array(Product::id());
            foreach ($productOptions as $key => $val) {
                $id = $val['product_option_id'];
                if (isset($o[$id]) && !empty($val['product_option_value'])) {
                    $add[] = $id;
                    if (is_array($o[$id])) {
                        $add[] = $o[$id][0];
                    } else {
                        $add[] = $o[$id];
                    }
                }
            }

            return implode(Config::$vSeparator, $add);
        }
        return null;
    }

    public static function addToCart($product_id, $quantity, $variation_id = null) {

        if ($variation_id !== null) {
            $variant = Product::searchForVariantId($variation_id);

            $id = $variation_id;
            $sku = isset($variant['sku']) ? $variant['sku'] : $id;
        } else {
            $id = $product_id;
            $sku = Product::sku();
        }
        if ($id === null && $sku === null) {
            return false;
        }

        self::$eventName = 'addToCart';

        self::$eventData = array(
            'product_id' => Product::id(),
            'quantity'=> (int) $quantity,
            'variation' => array(
                'id' => $id,
                'sku' => $sku
            )
        );
//Core::dd(self::$eventData);
        self::SessionSet();
    }

    public static function addToWishlist($product_id) {
        if (Product::sku() === null || Product::id() === null) {
            return false;
        }
        
        self::$eventName = 'addToWishlist';

        self::$eventData = array(
            'product_id' => $product_id,
            'variation' => array(
                'id' => $product_id,
                'sku' => Product::sku()
            )
        );

        self::SessionSet();
    }
    public static function removeFromWishlist($product_id) {
        self::$eventName = 'removeFromWishlist';

        self::$eventData = array(
            'product_id' => $product_id,
            'variation' => array(
                'id' => Product::id(),
                'sku' => Product::sku()
            )
        );

        self::SessionSet();
    }

    public static function removeFromCart($product_id, $quantity, $variation_id) {
        if ($variation_id !== null) {
            $variant = Product::searchForVariantId($variation_id);

            $id = $variation_id;
            $sku = isset($variant['sku']) ? $variant['sku'] : $id;
        } else {
            $id = $product_id;
            $sku = Product::sku();
        }

        if ($id === null && $sku === null) {
            return false;
        }

        self::$eventName = 'removeFromCart';

        self::$eventData = array(
            'product_id' => $product_id,
            'quantity'=> (int) $quantity,
            'variation' => array(
                'id' => $id,
                'sku' => $sku
            )
        );

        self::SessionSet();
    }

    public static function pushStatus()
    {
        FileSystem::setWorkDirectory('base');

        if (Config::getPushStatus() != 0) {
            FileSystem::writeFile("firebase-config.js", Config::getFireBase());
            FileSystem::writeFile("firebase-messaging-sw.js", Config::getFireBaseMessaging());
        } else {
            FileSystem::deleteFile("firebase-config.js");
            FileSystem::deleteFile("firebase-messaging-sw.js");
        }
    }

    public static function orderUp($oID, $status) {
        $send = array(
            'order_number' => $oID,
            'order_status' => $status
        );

        Api::send("update_order_status", $send, false);
    }

    public static function saveOrder($orderId)
    {
        self::$eventName = 'saveOrder';

        self::$eventData = $orderId;

        self::SessionSet($orderId);
    }

    public static function addSubscriber() {
        $info = array(
            "email" => Customer::email()
        );

        $name = array();

        if (Customer::firstname() !== null) {
            $name[] = Customer::firstname();
        }

        if (Customer::lastname() !== null) {
            $name[] = Customer::lastname();
        }

        if (empty($name))
        {
            $info["name"] = explode("@", Customer::email())[0];
        } else {
            $info["name"] = implode(" ", $name);
        }

        $phone = Customer::telephone();

        if (!empty($phone)) {
            $info["phone"] = $phone;
        }
        return $info;
    }

    public static function emailAndPhone($email)
    {
        Customer::getByEmail($email);

        $phone = Customer::telephone();

        if (!empty($phone)) {
            self::$eventName = "setPhone";

            self::$eventData = array(
                'phone' => $phone
            );

            self::SessionSet();
        }

        self::$eventName = 'setEmail';

        $send = array(
            'email_address' => Customer::email()
        );

        if (Customer::firstname() !== null) {
            $send['firstname'] = Customer::firstname();
            if (empty($send['firstname'])) {
                unset($send['firstname']);
            }
        }

        if (Customer::lastname() !== null) {
            $send['lastname'] = Customer::lastname();
            if (empty($send['lastname']))
            {
                unset($send['lastname']);
            }
        }

        self::$eventData = $send;

        self::SessionSet();
    }

    private static function SessionSet($key = null)
    {
        $add = Core::getSessionData(self::$eventName);

        if ($add === false) { $add = array(); }

        if ($key === null) {
            $add[] = self::$eventData;
        } else {
            $add[$key] = self::$eventData;
        }

        Core::setSessionData(self::$eventName, $add);
    }
}
