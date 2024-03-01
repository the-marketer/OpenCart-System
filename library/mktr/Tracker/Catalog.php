<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker;

use Mktr\Helper\Config;
use Mktr\Helper\Core;

trait Catalog {
    public static $Page = false;
    private static $conf = null;
    private static $load = false;
    private static $load2 = false;
    private static $route = null;
    private static $saveOrderDetect = false;

    public function __construct($registry) {
        parent::__construct($registry);
        self::init($registry, $this);

        if (array_key_exists('route', Core::request()->get)) {
            self::$route = Core::request()->get['route'];
        } else if (array_key_exists('_route_', Core::request()->get)) {
            self::$route = Core::request()->get['_route_'];
        }
    }

    public static function init($registry, $th){
        Core::init($th);
    }

    public static function conf() {
        if (self::$conf === null) {
            self::$conf = Config::init();
        }
        return self::$conf;
    }

    public static function route($route = null, $data = null) {

        if (!Config::getStatus()) {
            return ;
        }
        
        if ($route === 'error/not_found' && strstr(self::$route, 'mktr/api/') !== false) {
            $route = self::$route;
        } else if (strstr($route, 'mktr/api/') === false) {
            return ;
        }

        $p = explode('/', $route);

        self::$Page = empty($p[2]) ? false : $p[2];

        if (self::$Page !== false) {
            Route::checkPage(self::$Page);
        }

        exit();
    }

    public static function observer($route = null, $data = null) {
        Observer::init($route, $data);
    }

    public static function oc2($route = null, $data = null) {
        if (!Config::getStatus()) {
            return ;
        }

        if (self::$route === null) {
            if (array_key_exists('route', Core::request()->get)) {
                self::$route = Core::request()->get['route'];
            } else if (array_key_exists('_route_', Core::request()->get)) {
                self::$route = Core::request()->get['_route_'];
            }
        }
        
        if (self::$saveOrderDetect) {
            if(!empty(Core::session()->data) && array_key_exists('order_id', Core::session()->data)) {
                if (Core::session()->data['order_id'] !== null) {
                    Observer::saveOrder(Core::session()->data['order_id']);
                }
            }
        } else {
            if (empty(Core::getSessionData('mktr_order_id')) && !empty(Core::session()->data) && array_key_exists('order_id', Core::session()->data)) {
                Core::setSessionData('mktr_order_id', [ 'id' => Core::session()->data['order_id'] ]);
            }
        }

        if (!empty(Core::request()->get['route'])) {
            if (strpos(Core::request()->get['route'], "mktr/api/") !== false) {
                self::route(Core::request()->get['route'], $data);
            } else {
                Observer::init(Core::request()->get['route'], $data);
            }
        }
    }
    public static function links(&$route=null, &$data=null, &$template =null) {

    }

    public static function index() {
        if (!Config::getStatus() || self::$load) {
            return ;
        }
        self::$load = true;
        $out = Events::google_head() . Events::loader() . Events::loadEvents() .
        // Events::google_body().
        Events::loader_body();
        if (Core::getOcVersion() >= "2.0") {
            return $out;
        } else {
            Core::i()->output = $out;
        }
    }
    /** @noinspection PhpUnusedParameterInspection */
    public static function loader(&$route, &$data, &$output) {
        if (!Config::getStatus() || self::$load) {
            return ;
        }

        self::$load = true;

        $output = str_replace(
            array( '</head>', '</body>' ),
            array( Events::google_head() .
                Events::loader() .
                Events::loadEvents() .
                // Events::google_body().
                Events::loader_body() . '</head>',
                '</body>' ), $output);
    }

    public static function post_order_add($order_id) {
        Observer::saveOrder($order_id);
    }
    
    public static function pre_order_add($data) {
        // var_dump($data);
        // die();
    }
}
