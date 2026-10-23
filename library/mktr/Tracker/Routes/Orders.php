<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Core;
use Mktr\Helper\Valid;
use Mktr\Helper\FileSystem;
use Mktr\Tracker\Model\Order;

class Orders
{
    private static $init = null;

    private static $map = array(
        "fileName" => "orders",
        "secondName" => "order"
    );

    private static $lockFile = null;
    private static $skipFile = null;

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
            'limit' => self::getLimit(),
        );

        $skip = self::loadSkipList();

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
                $id = isset($val['order_id']) ? (int) $val['order_id'] : 0;

                /* comanda a doborât deja un request anterior */
                if (isset($skip[$id])) {
                    continue;
                }

                /* mai bine o pagină incompletă decât un proces omorât */
                if (self::lowOnMemory()) {
                    self::clearLock();
                    return $get;
                }

                /* dacă procesul moare aici, lock-ul rămâne pe disc */
                self::setLock($id);

                try {
                    $get[] = Order::selectOrder($key)->toExtraArray();
                } catch (\Exception $e) {
                    $skip[$id] = $id;
                    self::saveSkipList($skip);
                } catch (\Throwable $e) {
                    $skip[$id] = $id;
                    self::saveSkipList($skip);
                }

                self::clearLock();
            }

            $args['page']++;
        } while (0 < $pages);

        return $get;
    }

    private static function getLimit()
    {
        $limit = (int) Valid::getParam('limit', 250);

        if ($limit < 1 || $limit > 250) {
            $limit = 250;
        }

        return $limit;
    }

    private static function files()
    {
        if (self::$lockFile === null) {
            FileSystem::setWorkDirectory('Storage');

            self::$lockFile = 'orders.lock.' . Core::getStoreID() . '.txt';
            self::$skipFile = 'orders.skip.' . Core::getStoreID() . '.json';
        }
    }

    /**
     * Un lock rămas pe disc înseamnă că request-ul anterior a murit pe comanda
     * aia fără să apuce să ruleze vreun catch - fatal, OOM kill sau segfault.
     * O trecem pe lista de sărit, ca următorul request să treacă mai departe.
     */
    private static function loadSkipList()
    {
        self::files();

        $skip = array();

        $list = json_decode(FileSystem::rFile(self::$skipFile), true);

        if (is_array($list)) {
            foreach ($list as $v) {
                $skip[(int) $v] = (int) $v;
            }
        }

        $stuck = (int) FileSystem::rFile(self::$lockFile);

        if ($stuck > 0) {
            $skip[$stuck] = $stuck;
            self::saveSkipList($skip);
            self::clearLock();
        }

        return $skip;
    }

    private static function saveSkipList($skip)
    {
        self::files();

        FileSystem::writeFile(self::$skipFile, json_encode(array_values($skip)));
    }

    private static function setLock($id)
    {
        self::files();

        FileSystem::writeFile(self::$lockFile, (string) $id);
    }

    private static function clearLock()
    {
        self::files();

        FileSystem::writeFile(self::$lockFile, '');
    }

    private static function lowOnMemory()
    {
        $limit = self::memoryLimit();

        if ($limit <= 0) {
            return false;
        }

        return memory_get_usage(true) > ($limit * 0.8);
    }

    private static function memoryLimit()
    {
        $limit = trim(ini_get('memory_limit'));

        if ($limit === '' || (int) $limit === -1) {
            return 0;
        }

        $value = (int) $limit;

        switch (strtolower(substr($limit, -1))) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }

        return $value;
    }
}
