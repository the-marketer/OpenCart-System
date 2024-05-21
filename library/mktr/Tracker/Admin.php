<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker;

use Mktr\Helper\Core;
use Mktr\Helper\Config;
use Mktr\Helper\Logo;
use Mktr\Helper\Form;
use Mktr\Helper\Model\Events;
use Mktr\Helper\Model\mkConfig;
use Mktr\Helper\Model\Module;
use Mktr\Helper\Model\Settings;
use Mktr\Helper\Model\Store;

trait Admin {
    public static $conf = null;
    public static $events = array(
        'admin/view/common/column_left/before' => array(
            'links'
        ),
        'admin/controller/api/order/*/before' => array(
            'observer'
        ),
        'catalog/controller/api/order/history/before' => array(
            'observer'
        ),
        //'catalog/controller/api/order/edit/before' => array(
        //    'observer'
        //),
        'admin/controller/sale/order|call/before' => array(
            'observer'
        ),
        // api/order/edit
        'catalog/controller/mktr/api/*/before' => array(
            'route'
        ),
        'catalog/controller/error/not_found/before' => array(
            'route'
        ),
        'catalog/view/*/common/header/after' => array(
            'loader'
        ),
        'catalog/view/common/header/after' => array(
            'loader'
        ),
        'catalog/controller/checkout/*/before' => array(
            'observer'
        ),
        'catalog/controller/account/*/before' => array(
            'observer'
        ),
        'catalog/controller/extension/*/before' => array(
            'observer'
        ),
        'catalog/controller/rest/*/before' => array(
            'observer'
        ),
        'catalog/controller/journal3/*/before' => array(
            'observer'
        )
        /*
        New EVENT 1
        'catalog/controller/account/wishlist/before' => array(
            'observer'
        ),
        TODO: Event
        'catalog/controller/account/wishlist* /before' => array(
            'observer'
        ),
        */
    );

    private static $store_id = null;
    private static $linksAdd = true;

    private static $mkData = array();

    public static function conf() {
        if (self::$conf === null) {
            self::$conf = Config::init();
        }
        return self::$conf;
    }

    public function __construct($registry) {
        parent::__construct($registry);
        self::init($registry, $this);
    }

    public static function init($registry, $th){
        Core::init($th);
        if (Config::$addToModule) {
            if (isset(Core::request()->get['module_id'])) {
                $set = Module::getModule(Core::request()->get['module_id']);
                if (isset($set['store_id'])) {
                    self::$store_id = (int) $set['store_id'];
                }
            }
        }

        if(self::$store_id === null){
            if (isset(Core::request()->get['store_id'])) {
                self::$store_id = Core::request()->get['store_id'];
            }
        }

        Core::setStoreID((int) self::$store_id);
    }

    /** @noinspection PhpUnused */
    public static function checkModule() {
        if (Config::$addToModule) {
            $st = array();
            foreach (Module::getModulesByCode(Core::getModuleCode()) as $module) {
                $st[$module['setting']['store_id']] = $module;
            }

            $conf = Config::init();

            foreach (Store::getStores() as $__) {
                $id = $__['store_id'];
                $name = ($__['store_id']) . ' - ' . $__['name'];

                $data = array(
                    'name' => $name,
                    'store_id' => $id
                );

                if (isset($st[$id])) {
                    $data['status'] = (int) $st[$id]['setting']['status'];

                    if ($st[$id]['name'] !== $name) {
                        // $conf->set('module_id', $st[$id]['module_id']);
                        Settings::saveSetting('status', 1, $id);
                        if (Config::$addToModule) {
                            mkConfig::saveSetting('module_id', $st[$id]['module_id'], $id);
                            Module::editModule($st[$id]['module_id'], $data);
                        }
                    }
                } else {
                    $data['status'] = (int) mkConfig::getSettingValue('status',null, $id);

                    if (Config::$addToModule) {
                        $modId = Module::addModule($data);
                        // $conf->set('module_id', $modId);
                        mkConfig::saveSetting('module_id', $modId, $id);
                    }

                    Settings::saveSetting('status', 1, $id);
                }
                $conf->save();
            }
        }
    }

    public static function observer($route = null, $data = null) {
        // Core::dd($route, Core::request()->get['remove']);
        Observer::init($route, $data);
    }

    public static function oc2($route = null, $data = null) {
        if (!empty(Core::request()->get['route'])) {
            Observer::init(Core::request()->get['route'], $data);
        }
    }

    private static function getFormData() {
        if (!isset(self::$mkData["FormData"])) {
            $status = Core::ocModel('localisation/order_status')->getOrderStatuses();

            $refundStatus = array();

            foreach ($status as $v) {
                $refundStatus[] = array('value' => $v['order_status_id'], 'label' => $v['name']);
            }

            self::$mkData["FormData"] = array(
                array(
                    'status' => array(
                        'title'     => 'Status',
                        'type'      => 'select'
                    ),
                    /* Account Settings */
                    'tracking_key' => array(
                        'title'     => 'Tracking API Key *',
                        'type'      => 'text',
                        'holder'    => 'Your Tracking API Key.'
                    ),
                    'rest_key' => array(
                        'title'     => 'REST API Key *',
                        'type'      => 'text',
                        'holder'    => 'Your REST API Key.'
                    ),
                    'customer_id' => array(
                        'title'     => 'Customer ID *',
                        'type'      => 'text',
                        'holder'    => 'Your Customer ID.'
                    ),
                    'tit-sett' => array(
                        'title'     => '',
                        'type'      => 'empty',
                    ),
                    /* Cron Settings */
                    'cron_feed' => array(
                        'title'     => 'Activate Cron Feed',
                        'type'      => 'select',
                        'description' => '<b>If Enable, Please Add this to your server Cron Jobs</b><br /><code>0 * * * * /usr/bin/php ' .
                        MKTR_ROOT . 'system/library/mktr/cron.php > ' .
                        MKTR_ROOT . 'system/library/mktr/cron.log 2>&1</code><br />OR<br /><code>php ' .
                        MKTR_ROOT . 'system/library/mktr/cron.php</code>'
                    ),
                    'update_feed' => array(
                        'title'     => 'Cron Update feed every (hours)',
                        'type'      => 'text',
                        'description' => 'Set number of hours'
                    ),
                    'cron_review' => array(
                        'title'     => 'Activate Cron Review',
                        'type'      => 'select'
                    ),
                    'update_review' => array(
                        'title'     => 'Cron Update Review every (hours)',
                        'type'      => 'text',
                        'description' => 'Set number of hours'
                    ),
                    'tit-sett1' => array(
                        'title'     => '',
                        'type'      => 'empty',
                    ),
                    /* Extra Settings */
                    'opt_in' => array(
                        'title'     => 'Double opt-in setting',
                        'type'      => 'select',
                        'options' => array(
                            array('value' => 0, 'label' => 'WebSite'),
                            array('value' => 1, 'label' => 'The Marketer')
                        )
                    ),
                    'refund_status' => array(
                        'title'     => 'Refund Status',
                        'type'      => 'select',
                        'options'   => $refundStatus
                    ),
                    'push_status' => array(
                        'title'     => 'Push Notification',
                        'type'      => 'select'
                    ),
                    'default_stock' => array(
                        'title'     => 'Default Stock if negative Stock Value',
                        'type'      => 'select',
                        'options' => array(
                            array('value' => 0, 'label' => 'Out of Stock'),
                            array('value' => 1, 'label' => 'In Stock'),
                            array('value' => 2, 'label' => 'In supplier stock')
                        )
                    ),
                    'allow_export' => array(
                        'title'     => 'Allow orders export',
                        'type'      => 'select'
                    ),
                    'selectors' => array(
                        'title'     => 'Trigger Selectors',
                        'type'      => 'text',
                        'description' => 'Buttons that will trigger events Like AddToCart'
                    )
                ),
                array(
                    'brand' => array(
                        'title'     => 'Brand Attribute',
                        'type'      => 'text',
                        'description' => ''
                    ),
                    'color' => array(
                        'title'     => 'Color Attribute',
                        'type'      => 'text',
                        'description' => ''
                    ),
                    'size' => array(
                        'title'     => 'Size Attribute',
                        'type'      => 'text',
                        'description' => ''
                    ),
                )
            );
        }

        return self::$mkData["FormData"];
    }

    public function google() {
        Form::initProcess();

        if (Core::getOcVersion() >= "4") {
            $this->load->language('extension/opencart/module/category');
        }

        Core::i()->document->setTitle(Logo::getTitle('Google'));

        if (Core::getOcVersion() >= "2.0") {
            $data['header'] = Core::i()->load->controller('common/header');
            $data['column_left'] = Core::i()->load->controller('common/column_left');
            $data['footer'] = Core::i()->load->controller('common/footer');
        } else {
            $data['header'] = Core::i()->getChild('common/header');
            // $data['column_left'] = Core::i()->getChild('common/column_left');
            $data['footer'] = Core::i()->getChild('common/footer');
        }

        if (Core::getOcVersion() <= "2.2.9"){
            $cancel = Core::url()->link('extension/module', Core::token() . '&type=module', true);
        } else if (Core::getOcVersion() <= "2.4"){
            $cancel = Core::url()->link('extension/extension', Core::token() . '&type=module', true);
        } else {
            $cancel = Core::url()->link('marketplace/extension', Core::token() . '&type=module', true);
        }

        $action = Core::url()->link(Core::getLinkCus('mktr_google') . '&store_id=' . Core::getStoreID(), Core::token(), true);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'name' => Core::i()->language->get('text_home'),
            'href' => Core::url()->link('common/dashboard', Core::token(), true)
        );
        if (Core::getOcVersion() <= "2.2.9"){
            $data['breadcrumbs'][] = array(
                'name' => "Modules",
                'href' => Core::url()->link('extension/module', Core::token() . '&type=module', true)
            );
        } else if (Core::getOcVersion() <= "2.4"){
            $data['breadcrumbs'][] = array(
                'name' => "Extensions",
                'href' => Core::url()->link('extension/extension', Core::token() . '&type=module', true)
            );
        } else {
            $data['breadcrumbs'][] = array(
                'name' => "Extensions",
                'href' => Core::url()->link('marketplace/extension', Core::token() . '&type=module', true)
            );
        }

        $data['breadcrumbs'][] = array(
            'name' => Logo::getH1('Google') . ' >> ' . Config::init()->get('store_name'),
            'href' => $action
        );

        $out = array($data['header']);

        if (Core::getOcVersion() >= "2.0") {
            $out[] = $data['column_left'];
        }

        $form = self::$mkData["FormData"] = array(
            array(
                'google_status' => array(
                    'title'     => 'Status',
                    'type'      => 'select'
                ),
                /* Account Settings */
                'google_tracking' => array(
                    'title'     => 'Tracking Key *',
                    'type'      => 'text',
                    'holder'    => 'Your Google Tag Manager Tracking Key.'
                )
            )
        );
        Form::formFields($form[0]);

        $Form = Form::getForm();
        if (Core::getOcVersion() >= "2.0") {
            $o = array();
            $c = Core::getOcVersion() >= "4" ? ' class="breadcrumb-item"' : ' ';
            foreach ($data['breadcrumbs'] as $b) {
                $o[0][] = '<li' . $c . '><a href="' . $b['href'] . '">' . $b['name'] . '</a></li>';
            }

            foreach (Core::getChildren() as $b) {
                $o[1][] = '<li style="display: inline-block;text-shadow: 0 1px #fff;"><a href="' . $b['href'] . '">' . $b['name'] . '</a></li>';
            }

            $card = Core::getOcVersion() >= "4" ? ' class="card"' : ' class="panel panel-default"';
            $cardH = Core::getOcVersion() >= "4" ? ' class="card-header"' : ' class="panel-heading"';
            $cardB = Core::getOcVersion() >= "4" ? ' class="card-body"' : ' class="panel-body"';

            $out[] =  '<div id="content">
                <div class="page-header">
                    <div class="container-fluid">
                        <div class="' . (Core::getOcVersion() >= "4" ? "float-end" : "pull-right") . '">
                            <button type="submit" form="form-module" data-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fa fa-save"></i></button>
                            <a href="' . $cancel . '" data-toggle="tooltip" title="Cancel" class="btn btn-default"><i class="fa fa-reply"></i></a>
                        </div>';
            $out[] = '<div class="container-fluid"><' . (Core::getOcVersion() >= "4" ? "ol" : "ul") . ' class="breadcrumb">' . implode("", $o[0]) . '</' . (Core::getOcVersion() >= "4" ? "ol" : "ul") . '></div></div></div>';
            $out[] = '<div class="container-fluid"><div' . $card . '><div' . $cardH . '>
        <h3 class="panel-title">Select Store</h3>
      </div><div' . $cardB . '>' . implode("|",$o[1]) . '</div></div>';

            foreach (Form::getNotice() as $value) {
                $out[] = '<div class="alert alert-' . (isset($value['type']) ? $value['type'] : 'success') . ' alert-dismissible"><i class="fa ' . (isset($value['type']) ? 'fa-exclamation-circle' : 'fa-circle-info') . '"></i> ' . $value['message'] . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
            }

            $out[] = '<form method="POST" action="' . $action . '" enctype="multipart/form-data" class="form-horizontal" id="form-module">';
            $out[] = '<div' . $card . '><div' . $cardH . '>
        <h3 class="panel-title">' . Logo::getText('Main Settings') . '</h3>
      </div><div' . $cardB . '>' . $Form[0] . '</div></div>';

            $out[] = '</form>' . $data['footer'];
        } else {
            $o = array();
            foreach ($data['breadcrumbs'] as $b) {
                $o[0][] = '<a href="' . $b['href'] . '">' . $b['name'] . '</a>';
            }

            foreach (Core::getChildren() as $b) {
                $o[1][] = '<a style="text-decoration:none" href="' . $b['href'] . '">' . $b['name'] . '</a>';
            }
            $out[] = '<div id="content">';
            $out[] = '<div class="breadcrumb">' . implode(" :: ",$o[0]) . '</div>';
            $out[] = '<div class="box">
    <div class="heading">
        <h1>Select Store: ' . implode(" :: ",$o[1]) . '</h1>
        <div class="buttons">
            <a onclick="$(\'#form-module\').submit();" class="button">Save</a>
            <a href="' . $cancel . '" class="button">Cancel</a>
        </div>
    </div>';
$out[] = '<div class="content">';
$out[] = '<style>
.col-sm-10 {
    width: 83.3333333333%;
}
.col-sm-2 {
    width: 16.6666666667%;
}

.form-horizontal .control-label {
    text-align: right;
    margin-bottom: 0;
    padding-top: 9px;
    font-size: 14px;
    font-weight: bold;
}
.form-group {
    padding-top: 15px;
    padding-bottom: 15px;
    margin-bottom: 0;
}
.form-control {
    display: block;
    width: 100%;
    max-width: 700px;
    padding: 8px 13px !important;
    font-size: 13px;
    line-height: 1.428571429;
    margin-top: 8px;
    color: #555;
    background-color: #fff;
    background-image: none;
    border: 1px solid #ccc;
    border-radius: 3px;
    -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
    -webkit-transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
    -o-transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
    transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
}
select.form-control {
max-width: 728px !important;
}
</style>';
$out[] = '<form method="POST" action="' . $action . '" enctype="multipart/form-data" class="form-horizontal" id="form-module">';
$out[] = $Form[0];
$out[] = '</form>';
$out[] = '</div>';
$out[] = '</div>';
$out[] = '</div>';
            $out[] = $data['footer'];
        }

        Core::response()->setOutput(implode(" ", $out));
    }

    public function index() {
        if (Config::$addToModule) {
            self::checkModule();
        }

        Form::initProcess();

        if (Core::getOcVersion() >= "4") {
            $this->load->language('extension/opencart/module/category');
        }

        Core::i()->document->setTitle(Logo::getTitle('Tracker'));

        if (Core::getOcVersion() >= "2.0") {
            $data['header'] = Core::i()->load->controller('common/header');
            $data['column_left'] = Core::i()->load->controller('common/column_left');
            $data['footer'] = Core::i()->load->controller('common/footer');
        } else {
            $data['header'] = Core::i()->getChild('common/header');
            // $data['column_left'] = Core::i()->getChild('common/column_left');
            $data['footer'] = Core::i()->getChild('common/footer');
        }

        if (Core::getOcVersion() <= "2.2.9"){
            $cancel = Core::url()->link('extension/module', Core::token() . '&type=module', true);
        } else if (Core::getOcVersion() <= "2.4"){
            $cancel = Core::url()->link('extension/extension', Core::token() . '&type=module', true);
        } else {
            $cancel = Core::url()->link('marketplace/extension', Core::token() . '&type=module', true);
        }

        $action = Core::url()->link(Core::getLink() . '&store_id=' . Core::getStoreID(), Core::token(), true);

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'name' => Core::i()->language->get('text_home'),
            'href' => Core::url()->link('common/dashboard', Core::token(), true)
        );

        if (Core::getOcVersion() <= "2.2.9"){
            $data['breadcrumbs'][] = array(
                'name' => "Modules",
                'href' => Core::url()->link('extension/module', Core::token() . '&type=module', true)
            );
        } else if (Core::getOcVersion() <= "2.4"){
            $data['breadcrumbs'][] = array(
                'name' => "Extensions",
                'href' => Core::url()->link('extension/extension', Core::token() . '&type=module', true)
            );
        } else {
            $data['breadcrumbs'][] = array(
                'name' => "Extensions",
                'href' => Core::url()->link('marketplace/extension', Core::token() . '&type=module', true)
            );
        }

        $data['breadcrumbs'][] = array(
            'name' => Logo::getH1('Tracker') . ' >> ' . Config::init()->get('store_name'),
            'href' => $action
        );

        $out = array($data['header']);

        if (Core::getOcVersion() >= "2.0") {
            $out[] = $data['column_left'];
        }

        $form = self::getFormData();
        Form::formFields($form[0]);

        Form::formFields($form[1], 1);

        $Form = Form::getForm();
        if (Core::getOcVersion() >= "2.0") {
            $o = array();
            $c = Core::getOcVersion() >= "4" ? ' class="breadcrumb-item"' : ' ';
            foreach ($data['breadcrumbs'] as $b) {
                $o[0][] = '<li' . $c . '><a href="' . $b['href'] . '">' . $b['name'] . '</a></li>';
            }

            foreach (Core::getChildren() as $b) {
                $o[1][] = '<li style="display: inline-block;text-shadow: 0 1px #fff;"><a href="' . $b['href'] . '">' . $b['name'] . '</a></li>';
            }

            $card = Core::getOcVersion() >= "4" ? ' class="card"' : ' class="panel panel-default"';
            $cardH = Core::getOcVersion() >= "4" ? ' class="card-header"' : ' class="panel-heading"';
            $cardB = Core::getOcVersion() >= "4" ? ' class="card-body"' : ' class="panel-body"';

            $out[] =  '<div id="content">
                <div class="page-header">
                    <div class="container-fluid">
                        <div class="' . (Core::getOcVersion() >= "4" ? "float-end" : "pull-right") . '">
                            <button type="submit" form="form-module" data-toggle="tooltip" title="Save" class="btn btn-primary"><i class="fa fa-save"></i></button>
                            <a href="' . $cancel . '" data-toggle="tooltip" title="Cancel" class="btn btn-default"><i class="fa fa-reply"></i></a>
                        </div>';
            $out[] = '<div class="container-fluid"><' . (Core::getOcVersion() >= "4" ? "ol" : "ul") . ' class="breadcrumb">' . implode("", $o[0]) . '</' . (Core::getOcVersion() >= "4" ? "ol" : "ul") . '></div></div></div>';
            $out[] = '<div class="container-fluid"><div' . $card . '><div' . $cardH . '>
        <h3 class="panel-title">Select Store</h3>
      </div><div' . $cardB . '>' . implode("|",$o[1]) . '</div></div>';

            foreach (Form::getNotice() as $value) {
                $out[] = '<div class="alert alert-' . (isset($value['type']) ? $value['type'] : 'success') . ' alert-dismissible"><i class="fa ' . (isset($value['type']) ? 'fa-exclamation-circle' : 'fa-circle-info') . '"></i> ' . $value['message'] . '<button type="button" class="close" data-dismiss="alert">&times;</button></div>';
            }

            $out[] = '<form method="POST" action="' . $action . '" enctype="multipart/form-data" class="form-horizontal" id="form-module">';
            $out[] = '<div' . $card . '><div' . $cardH . '>
        <h3 class="panel-title">' . Logo::getText('Main Settings') . '</h3>
      </div><div' . $cardB . '>' . $Form[0] . '</div></div>';

            $out[] = '<div' . $card . '><div' . $cardH . '>
        <h3 class="panel-title">' . Logo::getText('Attribute Settings') . '</h3>
      </div><div' . $cardB . '>' . $Form[1] . '</div></div>';

            $out[] = '</form>' . $data['footer'];
        } else {
            $o = array();
            foreach ($data['breadcrumbs'] as $b) {
                $o[0][] = '<a href="' . $b['href'] . '">' . $b['name'] . '</a>';
            }

            foreach (Core::getChildren() as $b) {
                $o[1][] = '<a style="text-decoration:none" href="' . $b['href'] . '">' . $b['name'] . '</a>';
            }
            $out[] = '<div id="content">';
            $out[] = '<div class="breadcrumb">' . implode(" :: ",$o[0]) . '</div>';
            $out[] = '<div class="box">
    <div class="heading">
        <h1>Select Store: ' . implode(" :: ",$o[1]) . '</h1>
        <div class="buttons">
            <a onclick="$(\'#form-module\').submit();" class="button">Save</a>
            <a href="' . $cancel . '" class="button">Cancel</a>
        </div>
    </div>';
$out[] = '<div class="content">';
$out[] = '<style>
.col-sm-10 {
    width: 83.3333333333%;
}
.col-sm-2 {
    width: 16.6666666667%;
}

.form-horizontal .control-label {
    text-align: right;
    margin-bottom: 0;
    padding-top: 9px;
    font-size: 14px;
    font-weight: bold;
}
.form-group {
    padding-top: 15px;
    padding-bottom: 15px;
    margin-bottom: 0;
}
.form-control {
    display: block;
    width: 100%;
    max-width: 700px;
    padding: 8px 13px !important;
    font-size: 13px;
    line-height: 1.428571429;
    margin-top: 8px;
    color: #555;
    background-color: #fff;
    background-image: none;
    border: 1px solid #ccc;
    border-radius: 3px;
    -webkit-box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
    box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.075);
    -webkit-transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
    -o-transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
    transition: border-color ease-in-out 0.15s, box-shadow ease-in-out 0.15s;
}
select.form-control {
max-width: 728px !important;
}
</style>';
$out[] = '<form method="POST" action="' . $action . '" enctype="multipart/form-data" class="form-horizontal" id="form-module">';
$out[] = $Form[0];
$out[] = $Form[1];
$out[] = '</form>';
$out[] = '</div>';
$out[] = '</div>';
$out[] = '</div>';
            /*
            $out[] = '</div>';*/
            /*
    <div id="content">
      <div class="box">
        <div class="heading">
          <h1><img src="view/image/module.png" alt=""> Account</h1>
          <div class="buttons"><a onclick="$('#form').submit();" class="button">Save</a><a href="https://oc15.eaxdev.ga/admin/index.php?route=extension/module&amp;token=2559a69883c21361992efb35b5a3a35c" class="button">Cancel</a></div>
        </div>
    <div class="content">
      <form action="https://oc15.eaxdev.ga/admin/index.php?route=module/account&amp;token=2559a69883c21361992efb35b5a3a35c" method="post" enctype="multipart/form-data" id="form">
        <table id="module" class="list">
          <thead>
            <tr>
              <td class="left">Layout:</td>
              <td class="left">Position:</td>
              <td class="left">Status:</td>
              <td class="right">Sort Order:</td>
              <td></td>
            </tr>
          </thead>
                              <tbody id="module-row0">
            <tr>
              <td class="left"><select name="account_module[0][layout_id]">
                                                      <option value="6" selected="selected">Account</option>
                                                                        <option value="10">Affiliate</option>
                                                                        <option value="3">Category</option>
                                                                        <option value="7">Checkout</option>
                                                                        <option value="8">Contact</option>
                                                                        <option value="4">Default</option>
                                                                        <option value="1">Home</option>
                                                                        <option value="11">Information</option>
                                                                        <option value="5">Manufacturer</option>
                                                                        <option value="2">Product</option>
                                                                        <option value="9">Sitemap</option>
                                                    </select></td>
              <td class="left"><select name="account_module[0][position]">
                                    <option value="content_top">Content Top</option>
                                                      <option value="content_bottom">Content Bottom</option>
                                                      <option value="column_left">Column Left</option>
                                                      <option value="column_right" selected="selected">Column Right</option>
                                  </select></td>
              <td class="left"><select name="account_module[0][status]">
                                    <option value="1" selected="selected">Enabled</option>
                  <option value="0">Disabled</option>
                                  </select></td>
              <td class="right"><input type="text" name="account_module[0][sort_order]" value="1" size="3"></td>
              <td class="left"><a onclick="$('#module-row0').remove();" class="button">Remove</a></td>
            </tr>
          </tbody>
                              <tfoot>
            <tr>
              <td colspan="4"></td>
              <td class="left"><a onclick="addModule();" class="button">Add Module</a></td>
            </tr>
          </tfoot>
        </table>
      </form>
    </div>
  </div>
</div>
             */

            $out[] = $data['footer'];
        }

        Core::response()->setOutput(implode(" ", $out));
    }


    /**
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public static function links(&$route=null, &$data=null, &$template =null) {
        // Core::dd(array($route, $data, $template));

        if (!Core::user()->hasPermission('access', Core::getLink()) && self::$linksAdd === false) {
            return;
        }
        
        Logo::getTitle('Tracker');
        if (Core::getOcVersion() >= "2.3") {
            $newOder = array();
            foreach ($data['menus'] as $menu) {
                $newOder[] = $menu;

                if ($menu['id'] == 'menu-dashboard' && self::$linksAdd === true) {
                    self::$linksAdd = false;
                    $newOder[] = array(
                        'id' => 'menu-mktr',
                        'name' => Core::getOcVersion() >= "2.4" ? Logo::getMenuTitle2() : Logo::getTitle(),
                        'href' => '',
                        'icon' => Core::getOcVersion() >= "2.4" ? '' : 'fa-paper-plane',
                        'children' => Core::getChildren()
                    );
                }
            }
            $data['menus'] = $newOder;
        } else {

            $chil = '<ul>';

            foreach (Core::getChildren() as $key=>$val) {
                $chil .= '<li><a href="' . $val['href'] . '">' . $val['name'] . '</a></li>'; 
            }

            $chil .= '</ul>';

            $data['menu'] = str_replace(
                '<li id="catalog">',
                '<li id="mktr"><a class="parent"><i class="fa fa-paper-plane fa-fw"></i> <span>' . Logo::getTitle() . '</span></a>' . $chil . '</li><li id="catalog">',$data['menu']);
        }
        self::$linksAdd = false;    
    }

    public static function install()
    {
        mkConfig::ins();

        $status = Core::query("SELECT * FROM " . DB_PREFIX . "order_status WHERE language_id = '1'");
        $refundStatus = self::getRefundStatusFromList($status->rows);

        if ($refundStatus === null) {
            $status = Core::ocModel('localisation/order_status')->getOrderStatuses();
            $refundStatus = self::getRefundStatusFromList($status);
        }

        if ($refundStatus === null) {
            $refundStatus = 11;
        }

        foreach (Store::getStores() as $__) {
            Settings::saveSetting('status', 1, $__['store_id']);
            mkConfig::saveSetting('refund_status', $refundStatus, $__['store_id']);
        }
        if (Core::getOcVersion() >= "2.2"){
            if (Core::getOcVersion() >= "4.0.2") {
                foreach (self::$events as $trigger => $actions) {
                    foreach ($actions as $action) {
                        $ac = Core::getLink() . '.' . $action;
                        Events::addEvent(Core::getModuleCode(), $trigger, $ac);
                    }
                }
            } else {
                foreach (self::$events as $trigger => $actions) {
                    foreach ($actions as $action) {
                        $ac = Core::getOcVersion() >= "4" ? Core::getLink() . '|' . $action : Core::getLink() . '/' . $action;
                        Events::addEvent(Core::getModuleCode(), $trigger, $ac);
                    }
                }
            }
        } else {
            Core::i()->load->model('design/layout');
            Core::i()->load->model('setting/setting');

            $settings = array();

            // Add our module on every possible layout, custom or standard
            foreach (Core::i()->model_design_layout->getLayouts() as $layout) {
                $settings['mktr_tracker_module'][] = array(
                    'layout_id' => $layout['layout_id'],
                    'position' => 'content_bottom',
                    'status' => '1',
                    'sort_order' => '99',
                );
                if (Core::getOcVersion() < '2.2' && Core::getOcVersion() > '2.0') {
                    $q = Core::i()->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "layout_module WHERE code='mktr_tracker' AND layout_id = '{$layout['layout_id']}'");

                    if ($q->row['total'] == 0) {
                        Core::i()->db->query("
                        INSERT INTO " . DB_PREFIX . "layout_module SET
                        layout_id = '{$layout['layout_id']}',
                        code = 'mktr_tracker',
                        position = 'content_bottom',
                        sort_order = '99'");
                    }
                }
            }
            if (Core::getOcVersion() < '2.2') {
                $settings['mktr_tracker_status'] = 1;
                if (Core::getOcVersion() > '2.0') {
                    foreach (array( 'pre.order.add' => array('pre_order_add'), 'post.order.add' => array('post_order_add') ) as $trigger => $actions) {
                        foreach ($actions as $action) {
                            Events::addEvent(Core::getModuleCode(), $trigger, Core::getLink() . '/' . $action);
                        }
                    }
                }
            }

            Core::i()->model_setting_setting->editSetting('mktr_tracker', $settings);
        }

        self::checkModule();
    }

    public static function uninstall()
    {
        $u20 = Core::getOcVersion() >= "2.0";

        if ($u20 && Core::getOcVersion() <= "2.4"){
            $do = Core::i()->model_extension_extension;
        } else {
            $do = Core::i()->model_setting_extension;
        }

        $do->uninstall('module', 'mktr_google');

        Settings::deleteSetting(Core::getCode());

        if ($u20) {
            Events::deleteEventByCode(Core::getModuleCode());
            if (Config::$addToModule) {
                Module::deleteModulesByCode(Core::getModuleCode());
            }
        }
        if (Core::getOcVersion() < '2.2' && Core::getOcVersion() > '2.0') {
            Core::i()->db->query("DELETE FROM " . DB_PREFIX . "layout_module WHERE `code` = 'mktr_tracker'");
            Events::deleteEventByCode(Core::getModuleCode());
        }

        mkConfig::drop();
    }

    private static function getRefundStatusFromList($statusList) {
        $refundStatus = null;
        foreach ($statusList as $v) {
            if ($v['name'] == 'Refunded') {
                $refundStatus = $v['order_status_id'];
            }
        }
        return $refundStatus;
    }

    public static function uninstallgoogle() {
        if (mkConfig::checkTable()) {
            $conf = Config::init();

            $conf->set('google_status', 0);
            $conf->set('google_tracking', '');
            $conf->save();
        }
    }
    public static function installgoogle() {
        if (!mkConfig::checkTable()) {
            if (Core::getOcVersion() >= "2.0" && Core::getOcVersion() <= "2.4"){
                $do = Core::i()->model_extension_extension;
            } else {
                $do = Core::i()->model_setting_extension;
            }

            $do->uninstall('module', 'mktr_google');

            $msg = 'Please install The Marketer - Tracker First';


            if (Core::getOcVersion() >= "4") {
                echo json_encode(array('error'=> $msg));
                die();
            } /* else {
                // Core::i()->session->data['error'] = $msg;
                // Core::i()->response->addHeader('Content-Type: application/json');
		        // Core::i()->response->setOutput($msg);
                // Core::i()->session->data['error'] = $msg;
                // Core::i()->session->data['success'] = null;
                // return ['error'=> $msg];
            }
            */
        } else if (Core::getOcVersion() >= "3") {
            Settings::editSetting(array('status'=>1), $store_id = null, Core::getCodeCus('mktr_google'));
        }
    }
}
