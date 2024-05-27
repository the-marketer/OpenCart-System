<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

use Mktr\Helper\Model\Store;

/**
 * @property $db
 * @property $controller
 *
 * @method static query(string $string)
 * @method static escape(mixed $value)
 * @method static cache(string $string)
 * @method static config(string $string, mixed $value = null)
 * @method static ocConfig(string $string, mixed $value = null)
 * @method static setCache(string $string, mixed $value = null)
 * @method static i()
 * @method static lastId()
 * @method static user()
 * @method static url()
 * @method static session()
 * @method static request()
 * @method static setOcConfig(int|string $key, mixed $value)
 * @method static response()
 * @method static ocModel(mixed|null $id)
 * @method static customer()
 */

class Core
{
    private static $init = null;
    private static $storeID = null;
    /**
     * @var array
     */
    private static $children = null;
    private static $loaded;
    private static $tkn = null;
    private static $data = array();

    public static function init($controller = null)
    {
        if ($controller !== null) {
            self::$init = $controller;
        }
        return self::$init;
    }

    public static function __callStatic($name, $arg)
    {
        $send = null;
        switch ($name) {
            case "setCache":
                if (Core::getOcVersion() >= '2.0') {
                    self::$init->cache->set($arg[0], $arg[1]);
                }
                $send = self::$init;
                break;
            case "cache":
                if (Core::getOcVersion() >= '2.0') {
                    $send = self::$init->cache->get($arg[0]);
                }
                break;
            case "escape":
                $send = self::$init->db->escape($arg[0]);
                break;
            case "response":
                $send = self::$init->response;
                break;
            case "request":
                $send = self::$init->request;
                break;
            case "user":
                $send = self::$init->user;
                break;
            case "customer":
                $send = self::$init->customer;
                break;
            case "url":
                $send = self::$init->url;
                break;
            case "session":
                $send = self::$init->session;
                break;
            case "query":
                $send = self::$init->db->query($arg[0]);
                break;
            case "lastId":
                $send = self::$init->db->getLastId();
                break;
            case "ocConfig":
                $v = self::$init->config->get($arg[0]);
                $send = $v === null && isset($arg[1]) ? $arg[1] : $v;
                break;
            case "setOcConfig":
                self::$init->config->set($arg[0], $arg[1]);
                $send = self::$init;
                break;
            case "ocModel":
                if (!isset(self::$loaded[$arg[0]])) {
                    self::$init->load->model($arg[0]);
                    self::$loaded[$arg[0]] = self::$init->{"model_" . str_replace('/','_', $arg[0])};
                }
                $send = self::$loaded[$arg[0]];
                break;
            default:
                $send = self::$init;
        }
        return $send;
    }

    public static function token() {
        if (self::$tkn === null) {
            self::$tkn =
                isset(Core::session()->data['user_token']) ?
                    'user_token=' . Core::session()->data['user_token'] : 'token=' . Core::session()->data['token'];
        }
        return self::$tkn;
    }

    public static function searchIn($array, $field, $idField = null) {
        $out = array();

        if (is_array($array)) {
            foreach ($array as $value) {
                if ($idField == null) {
                    $out[] = $value[$field];
                } else {
                    $out[$value[$idField]] = $value[$field];
                }
            }
            return $out;
        } else {
            return false;
        }
    }

    public static function digit2($num)
    {
        return number_format((float) $num, 2, '.', ',');
    }

    public static function getSessionData($name = null) {
        if (isset(Core::session()->data['Mktr_' . $name])) {
            return Core::session()->data['Mktr_' . $name];
        }
        return array();
    }

    public static function setSessionData($name, $value) {
        Core::session()->data['Mktr_' . $name] = $value;
        return self::$init;
    }

    public static function getOcVersion() {
        if (!isset(self::$data['VERSION'])) {
            self::$data['VERSION'] = defined('VERSION') ? VERSION : "3.0";
        }
        return self::$data['VERSION'];
    }

    public static function setStoreID($id = null) {
        self::$storeID = $id === null ? (int) Core::ocConfig('config_store_id', 0) : $id;
        return self::$init;
    }

    public static function getStoreID() {
        if (self::$storeID === null) {
            self::setStoreID();
        }
        return self::$storeID;
    }

    public static function getModuleCode() {
        return Config::$code;
    }

    /** @noinspection PhpUnused */
    public static function getCode() {
        return Core::getOcVersion() >= '3.0' ? 'module_' . Config::$code : Config::$code;
    }

    public static function getCodeCus($code) {
        return Core::getOcVersion() >= '3.0' ? 'module_' . $code : $code;
    }

    /** @noinspection PhpUnused */
    public static function getLanguagePath() {
        return Core::getOcVersion() >= '2.2' ? 'en-gb': 'english';
    }

    /** @noinspection PhpUnused */
    public static function getControllerPath() {
        return Core::getOcVersion() >= '4' ? 'extension/mktr/module' : (Core::getOcVersion() >= '2.3' ? 'extension/module': 'module');
    }

    /** @noinspection PhpUnused */
    public static function getLink() {
        return self::getControllerPath() . '/' . self::getModuleCode();
    }

    /** @noinspection PhpUnused */
    public static function getLinkCus($code) {
        return self::getControllerPath() . '/' . $code;
    }


    public static function getChildren() {
        if (self::$children === null) {
            self::$children = array();

            foreach (Store::getStores() as $store) {
                self::$children[] = array(
                    'name' => $store['name'],
                    'children' => array(),
                    'href' => Core::url()->link(Core::getLink(), 'store_id=' . $store['store_id'] . '&' . Core::token(), true)
                );
            }
        }
        return self::$children;
    }

    public static function dd() {
        if (isset($_COOKIE['EAX_DEBUG'])) {
            echo "<pre>";
            foreach (func_get_args() as $v){
                var_dump($v);
            }
            echo "</pre>";
            exit;
        }
    }

}
