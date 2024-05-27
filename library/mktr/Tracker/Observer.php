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
        'checkout/cart|add' => 'addToCart',
        'checkout/cart|remove' => 'removeFromCart',
        'checkout/cart.add' => 'addToCart',
        'checkout/cart.remove' => 'removeFromCart',
        'checkout/cart' => 'removeFromCart',
        
        'checkout/register.save' => 'saveOrder',
        'checkout/register/save' => 'saveOrder',
        'checkout/register|save' => 'saveOrder',
        'checkout/success' => 'saveOrder',

        'extension/payment/cod/confirm' => 'saveOrder',
        'extension/payment/ipay/confirm' => 'saveOrder',

        'account/wishlist/add' => 'addToWishlist',
        'account/wishlist/remove' => 'removeFromWishlist',
        'account/wishlist|add' => 'addToWishlist',
        'account/wishlist|remove' => 'removeFromWishlist',
        'account/wishlist.add' => 'addToWishlist',
        'account/wishlist.remove' => 'removeFromWishlist',
        'account/wishlist' => 'removeFromWishlist',

        'account/register' => 'RegisterOrLogIn',
        'account/register|register' => 'RegisterOrLogIn',
        'account/register.register' => 'RegisterOrLogIn',
        'account/register/register' => 'RegisterOrLogIn',
        
        'account/login' => 'RegisterOrLogIn',
        'account/login|login' => 'RegisterOrLogIn',
        'account/login.login' => 'RegisterOrLogIn',
        'account/login/login' => 'RegisterOrLogIn',
        
        'account/newsletter' => 'RegisterOrLogIn',
        'account/newsletter|save' => 'RegisterOrLogIn',
        'account/newsletter.save' => 'RegisterOrLogIn',
        'account/newsletter/save' => 'RegisterOrLogIn',

        'journal3/settings' => 'RegisterOrLogIn',
        'journal3/newsletter/newsletter' => 'RegisterOrLogInJournal',

        'api/order/edit' => 'orderUp',
        'api/order/history' => 'orderUp',
        'api/order|edit' => 'orderUp',
        'api/order.edit' => 'orderUp',
        'api/order|history' => 'orderUp',
        'api/order.history' => 'orderUp',
        'sale/order/history' => 'orderUp',
        'sale/order|history' => 'orderUp',
        'sale/order.history' => 'orderUp',
        'sale/order|call' => 'orderUp',
        'sale/order.call' => 'orderUp',

        'rest/Custom_API_MTF_admin/FinalizeOrder' => 'orderUpCargus',
        'rest/order_admin/orderhistory' => 'orderUpCargus2',

        'module/cart' => 'remove'
        /* 
        New EVENT 2
        */
    );

    private static $do = true;

    private static $defPostAddRemove = array(
        'product_id' => null,
        'quantity' => 1,
        'option' => null
    );

    public static function fixProductID($product_id) {
        $id = explode('/', $product_id);
        foreach ($id as $key => $val) {
            if (empty($val)) {
                unset($id[$key]);
            }
        }
        return implode('/', $id);
    }

    public static function init($route = null, $data = null)
    {
        if (self::$init == null) { self::$init = new self(); }

        if (self::$do === false) { return self::$init; }

        if ($route !== null) {
            if (array_key_exists('order_id', Core::session()->data)) {
                Core::setSessionData('tmp_order_id', [ 'id' => Core::session()->data['order_id'] ]);
            }
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
                        
                        $variant = null;

                        if (isset($p['option']) && $p['option'] !== null) {
                            $variant = self::getVariantID($p['option']);
                        }

                        self::$do = false;
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

                            self::$do = false;
                            self::removeFromCart($p['product_id'], $p['quantity'], $variant);

                        }
                        break;
                    case 'checkout/cart|remove':
                    case 'checkout/cart/remove':
                    case 'checkout/cart.remove':
                        $item = Product::getCartItem(Core::request()->post['key']);
                        
                        if (isset($item['option'])) {
                            /** @noinspection PhpComposerExtensionStubsInspection */
                            $item['option'] = json_decode($item['option'], true);
                        }

                        $p = array_merge(self::$defPostAddRemove, $item);

                        Product::getById($p['product_id']);

                        $variant = self::getVariantID($p['option']);

                        self::$do = false;
                        self::removeFromCart($p['product_id'], $p['quantity'], $variant);
                    break;
                    case 'account/wishlist|add':
                    case 'account/wishlist/add':
                    case 'account/wishlist.add':
                        $product_id = self::fixProductID(Core::request()->post['product_id']);
                        Product::getById($product_id);

                        self::$do = false;
                        self::addToWishlist($product_id);
                    break;
                    case 'account/wishlist|remove':
                    case 'account/wishlist/remove':
                    case 'account/wishlist.remove':
                        $product_id = self::fixProductID(Core::request()->post['product_id']);
                        Product::getById($product_id);

                        self::$do = false;
                        self::removeFromWishlist($product_id);
                        break;
                    case 'account/wishlist':
                        if (isset(Core::request()->get['remove'])) {
                            Product::getById(Core::request()->get['remove']);

                            self::$do = false;
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
                            self::$do = false;
                            self::saveOrder($orderID);
                        }
                    break;
                    case 'checkout/register.save':
                    case 'checkout/register/save':
                    case 'checkout/register|save':
                        self::$eventName = 'setEmail';
                        
                        self::$eventData = array( 'email_address' => Core::request()->post['email'] );

                        if (isset(Core::request()->post['newsletter']) && Core::request()->post['newsletter'] == 1) {
                            self::$eventData['unsubscribe'] = false;
                        }
                        
                        self::$do = false;
                        self::SessionSet(Core::request()->post['email']);
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
                            self::$do = false;
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
                            self::$do = false;
                            self::orderUp($oId, $status);
                        }
                    break;
                    case 'rest/Custom_API_MTF_admin/FinalizeOrder':
                        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                            $input = file_get_contents('php://input');
                            $data = json_decode($input, true);

                            if (isset($data['OrderID'])) {
                                $status = Order::getFromStatusList(200);
                                self::$do = false;
                                self::orderUp($data['OrderID'], $status);
                            }
                        }
                    break;
                    case 'rest/order_admin/orderhistory':
                        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                            if (isset(Core::request()->get['id'])) {
                                $id = Core::request()->get['id'];
                                $input = file_get_contents('php://input');
                                $data = json_decode($input, true);
                                if (isset($data['order_status_id'])) {
                                    $status = Order::getFromStatusList($data['order_status_id']);
                                    self::$do = false;
                                    self::orderUp($id, $status);
                                }
                            }
                        }
                    break;
                    case 'account/newsletter/save':
                    case 'account/newsletter|save':
                    case 'account/newsletter.save':
                    case 'account/newsletter':
                    case 'journal3/settings':
                        if (isset(Core::request()->post['newsletter'])) {
                            self::$eventName = 'setEmail';
                            
                            self::$eventData = array(
                                'email_address' => Core::customer()->getEmail(),
                                'unsubscribe' => false
                            );
                            
                            if (Core::request()->post['newsletter'] == 0) {
                                self::$eventData['unsubscribe'] = true;
                            }
                            
                            self::$do = false;
                            self::SessionSet(Core::customer()->getEmail());
                        }
                    break;
                    case 'journal3/newsletter/newsletter':
                        if (isset(Core::request()->post['email'])) {
                            self::$eventName = 'setEmail';
                            
                            self::$eventData = array(
                                'email_address' => Core::request()->post['email'],
                                'unsubscribe' => false
                            );
                            
                            if (isset(Core::request()->get['unsubscribe'])) {
                                self::$eventData['unsubscribe'] = true;
                            }
                            
                            self::$do = false;
                            self::SessionSet(Core::request()->post['email']);
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

        if (Customer::status() == Customer::STATUS_SUBSCRIBED)
        {
            $send['unsubscribe'] = false;
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
