<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker;

use Mktr\Helper\Core;
use Mktr\Helper\Valid;
use Mktr\Helper\Config;
use Mktr\Helper\FileSystem;
use Mktr\Tracker\Routes\Cron;
use Mktr\Tracker\Routes\Feed;
use Mktr\Tracker\Routes\Brands;
use Mktr\Tracker\Routes\Orders;
use Mktr\Tracker\Routes\Reviews;
use Mktr\Tracker\Routes\Category;
use Mktr\Tracker\Routes\setEmail;
use Mktr\Tracker\Routes\saveOrder;
use Mktr\Tracker\Routes\loadEvents;
use Mktr\Tracker\Routes\clearEvents;
use Mktr\Tracker\Routes\CodeGenerator;

class Route
{
    private static $init = null;

    private static $check = array(
        'Orders' => array(
            'key' => 'Required|Key|allow_export',
            'start_date' => 'Required|DateCheck|StartDate',
            'page' => null,
            'customerId' => null
        ),
        'CodeGenerator' => array(
            'key' => 'Required|Key',
            'expiration_date' => 'DateCheck',
            'value' => 'Required|Int',
            'type' => "Required|RuleCheck"
        ),
        'Reviews' => array(
            'key' => 'Required|Key',
            //'start_date' => 'Required|DateCheck|StartDate'
        ),
        'Feed' => array(
            'key' => 'Required|Key'
        ),
        'Cron' => array(
            'key' => 'Required|Key'
        ),
        'Brands' => array(
            'key' => 'Required|Key'
        ),
        'Category' => array(
            'key' => 'Required|Key'
        )
    );
    private static $defMime = array(
        'Orders' => 'json',
        'CodeGenerator' => 'json',
        'Reviews' => 'json',
        'Feed' => 'xml',
        'Cron' => 'xml',
        'Brands' => 'xml',
        'Category' =>'xml',
        'loadEvents' => 'js',
        'clearEvents' => 'js',
        'setEmail' => 'js',
        'saveOrder' => 'js'
    );

    private static $isStatic = array(
        'Orders' => true,
        'Feed' => true,
        'Brands' => true,
        'Category' => true
    );

    private static $allMethods = null;

    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function checkPage($p)
    {
        if (self::$allMethods == null)
        {
            foreach (get_class_methods(self::init()) as $value) {
                self::$allMethods[strtolower($value)] = $value;
            }
        }

        $p = strtolower($p);
        if(isset(self::$allMethods[$p]))
        {
            $page = self::$allMethods[$p];
            self::check($page);

            if (!Valid::status())
            {
                echo Valid::Output('status', Valid::error());
            }
            exit();
        }
    }

    /** @noinspection PhpReturnValueOfMethodIsNeverUsedInspection */
    private static function check($name)
    {
        if (isset(self::$defMime[$name]))
        {
            Valid::getParam('mime-type', self::$defMime[$name]);
        }

        if (isset(self::$check[$name]) && !Valid::check(self::$check[$name])->status())
        {
            return false;
        }
        if (in_array($name, array('Orders', 'Feed', 'Brands', 'Category'))) {
            ini_set('memory_limit', '10G');
            ini_set('max_execution_time', '3600');
        }

        $run = self::$name();

        if (isset(self::$isStatic[$name]))
        {
            $read = Valid::getParam('read');
            $file = Valid::getParam('file');
            $no_save = Valid::getParam('no_save', 0);
            $page = Valid::getParam('page');
            $start_date = Valid::getParam('start_date');

            $add = '';
            $script = '';

            if ($page !== null) {
                $add = '.' . $page;
                $limit = Valid::getParam('limit');
                
                if ($limit !== null) {
                    $add = '.' . $page . '.' . $limit;
                } else {
                    $add = '.' . $page;
                }
            }
            if ($start_date !== null) {
                $script = '.' . base64_encode($start_date);
            }

            $fileName = $run->get('fileName') . '.' . Core::getStoreID() . $add . $script . "." . Valid::getParam('mime-type',Config::defMime);

            if ($file !== null) {
                header('Content-Disposition: attachment; filename=' . $fileName);
            }

            FileSystem::setWorkDirectory('Storage');

            if ($read !== null && FileSystem::fileExists($fileName)) {
                echo Valid::Output(FileSystem::readFile($fileName));
            } else {
                echo Valid::Output($run->get('fileName'), array($run->get('secondName') => $run->execute()));
                if ($no_save != 1) {
                    FileSystem::writeFile($fileName, Valid::getOutPut());
                }
            }
        } else {
            echo Valid::Output($run->execute());
        }

        return true;
    }

    /* Pages */

    /** @noinspection PhpUnused */
    private static function Feed()
    {
        return Feed::init();
    }

    /** @noinspection PhpUnused */
    private static function Cron()
    {
        return Cron::init();
    }

    /** @noinspection PhpUnused */
    public static function clearEvents() {
        return clearEvents::init();
    }

    /** @noinspection PhpUnused */
    private static function CodeGenerator()
    {
        return CodeGenerator::init();
    }

    public static function Orders()
    {
        return Orders::init();
    }
    public static function Category()
    {
        return Category::init();
    }

    public static function Brands()
    {
        return Brands::init();
    }
    public static function Reviews()
    {
        return Reviews::init();
    }

    /** @noinspection PhpUnused */
    public static function loadEvents()
    {
        return loadEvents::init();
    }

    public static function setEmail()
    {
        return setEmail::init();
    }

    /** @noinspection PhpUnused */
    public static function saveOrder()
    {
        return saveOrder::init();
    }
}
