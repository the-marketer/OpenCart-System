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

    public function __construct($registry) {
        parent::__construct($registry);
        self::init($registry, $this);
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

    public static function index() {
        if (!Config::getStatus() || self::$load) {
            return ;
        }
        self::$load = true;
        $out = Events::google_head(). Events::loader(). Events::loadEvents().
        // Events::google_body().
        Events::loader_body();
        if (Core::getOcVersion() >= "2.0") {
            echo $out;
        } else {
            Core::i()->output = $out;
        }
    }

    public static function route($route = null, $data = null) {
        if (!Config::getStatus()) {
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
    /** @noinspection PhpUnusedParameterInspection */
    public static function loader(&$route, &$data, &$output) {
        if (!Config::getStatus() || self::$load) {
            return ;
        }

        self::$load = true;

        $output = str_replace(
            array('</head>',
                '</body>'
            ),
            array(
                Events::google_head().
                Events::loader().
                Events::loadEvents().
                // Events::google_body().
                Events::loader_body().'</head>',
               '</body>'
            ), $output);
    }
}