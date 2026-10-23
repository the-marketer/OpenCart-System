<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Api;
use Mktr\Helper\Config;
use Mktr\Helper\Core;
use Mktr\Helper\OrderLogs;
use Mktr\Tracker\Model\Order;
use Mktr\Helper\FileSystem;
use Mktr\Tracker\Routes\Feed;
use Mktr\Tracker\Routes\Reviews;
use Mktr\Helper\Valid;

use Mktr\Helper\Data;

class Cron
{
    private static $init = null;

    private static $map = array(
        "fileName" => "cron",
        "secondName" => "cron"
    );

    public static function get($f = 'fileName'){
        if (isset(self::$map[$f]))
        {
            return self::$map[$f];
        }
        return null;
    }

    public static function init()
    {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    /* comenzi care chiar au nevoie de un apel la API, pe o rulare */
    const RECONCILE_BUDGET = 50;
    /* + până la 2 apeluri x 10s pentru comanda în lucru, sub cele 60s ale file_get_contents din cron.php */
    const RECONCILE_SECONDS = 30;
    /* rânduri din `order` citite pe o rulare (cele deja la zi nu costă apeluri) */
    const RECONCILE_SCAN = 5000;
    const RECONCILE_PAGE = 200;
    /* la prima rulare nu avem cursor: ne uităm o zi în urmă */
    const FIRST_RUN_HOURS = 24;
    /* comenzile mai noi de atât sunt încă treaba browserului (saveOrder) */
    const BROWSER_MINUTES = 10;
    /* cât de departe în urmă comparăm statusurile direct (vezi sweepRecent) */
    const SWEEP_DAYS = 30;

    private static $started = 0;
    private static $used = 0;

    /**
     * Aduce theMarketer la zi cu comenzile magazinului curent:
     *  - comanda nu a ajuns deloc       -> save_order + update_order_status
     *  - statusul diferă de cel trimis  -> update_order_status
     *
     * Merge înainte pe `date_modified` cu un cursor salvat, deci prinde și
     * schimbările de status făcute de plăți, curieri sau module care scriu direct
     * în baza de date. Ce eșuează se reîncearcă de cel mult OrderLogs::MAX_TRIES ori.
     *
     * @param string|null $from backfill: verifică toate comenzile plasate de la data asta
     */
    public static function reconcileOrders($from = null) {
        if (Config::getStatus() == 0 || !Config::getRestKey()) {
            return array('skipped' => 'disabled');
        }

        $lock = self::lock();

        if ($lock === false) {
            return array('skipped' => 'running');
        }

        self::$started = time();
        self::$used = 0;

        @set_time_limit(120);
        Api::setTimeOut(10);

        $logs = OrderLogs::i();
        $result = array('sent' => 0, 'status' => 0, 'failed' => 0);

        try {
            self::retryFailed($logs, $result);
            self::syncRecent($logs, $result);
            self::sweepRecent($logs, $result);

            if ($from !== null) {
                $result['backfill_done'] = self::backfill($logs, $from, $result) ? 1 : 0;
            }
        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        $logs->save();

        Api::setTimeOut(1);
        self::unlock($lock);

        return $result;
    }

    private static function hasBudget() {
        return self::$used < self::RECONCILE_BUDGET && (time() - self::$started) < self::RECONCILE_SECONDS;
    }

    /**
     * @return bool true dacă theMarketer e la zi cu comanda
     */
    private static function syncOrder($logs, $id, $statusId, &$result) {
        $known = $logs->statusOf($id);

        if ($known !== null && $known === (int) $statusId) {
            return true;
        }

        self::$used++;

        try {
            if ($known === null) {
                Order::getById($id);

                if (Order::id() === null) {
                    $logs->clearRetry($id);
                    return true;
                }

                Api::send("save_order", Order::toApi());

                if (Api::getStatus() != 200) {
                    return self::failed($logs, $id, $result);
                }

                /* a ajuns; statusul vine imediat mai jos */
                $logs->markSent($id, OrderLogs::UNKNOWN);
                $result['sent']++;
            }

            Api::send("update_order_status", array(
                'order_number' => $id,
                'order_status' => Order::getFromStatusList($statusId)
            ), false);

            if (Api::getStatus() != 200) {
                return self::failed($logs, $id, $result);
            }

            $logs->markSent($id, $statusId);
            $result['status']++;
        } catch (\Exception $e) {
            return self::failed($logs, $id, $result);
        } catch (\Throwable $e) {
            return self::failed($logs, $id, $result);
        }

        /* dacă procesul moare pe drum, să nu retrimitem tot ce a mers deja */
        if (self::$used % 10 == 0) {
            $logs->save();
        }

        return true;
    }

    private static function failed($logs, $id, &$result) {
        $logs->addRetry($id);
        $result['failed']++;

        return false;
    }

    private static function retryFailed($logs, &$result) {
        $retry = $logs->getRetries();

        if (empty($retry)) {
            return;
        }

        $ids = array();

        foreach (array_keys($retry) as $id) {
            $ids[] = (int) $id;
        }

        $rows = Core::query("SELECT `order_id`, `order_status_id`, `store_id` FROM `" . DB_PREFIX . "order`" .
            " WHERE `order_id` IN (" . implode(',', $ids) . ")")->rows;

        $found = array();

        foreach ($rows as $row) {
            $found[(int) $row['order_id']] = true;

            /* lista e comună; fiecare magazin își trimite comenzile cu cheia lui */
            if ((int) $row['store_id'] !== (int) Core::getStoreID()) {
                continue;
            }

            if ((int) $row['order_status_id'] === 0) {
                $logs->clearRetry((int) $row['order_id']);
                continue;
            }

            if (!self::hasBudget()) {
                return;
            }

            self::syncOrder($logs, (int) $row['order_id'], (int) $row['order_status_id'], $result);
        }

        /* comenzi șterse între timp din magazin */
        foreach ($ids as $id) {
            if (!isset($found[$id])) {
                $logs->clearRetry($id);
            }
        }
    }

    private static function syncRecent($logs, &$result) {
        $key = 'cursor_' . (int) Core::getStoreID();
        $cursor = $logs->getMeta($key);

        if (!is_array($cursor) || !isset($cursor[0], $cursor[1])) {
            /* NOW() din MySQL, nu din PHP: date_modified e scris cu ceasul bazei de date */
            $row = Core::query("SELECT DATE_SUB(NOW(), INTERVAL " . self::FIRST_RUN_HOURS . " HOUR) AS `d`")->row;
            $cursor = array($row['d'], 0);
        }

        $scanned = 0;

        while ($scanned < self::RECONCILE_SCAN && self::hasBudget()) {
            $date = Core::escape($cursor[0]);

            /*
             * Momentul ultimei schimbări = cel mai nou dintre date_modified și ultimul
             * rând din order_history. OC 4.1 nu mai atinge date_modified când schimbă
             * statusul, dar toate versiunile (1.5 - 4.1) scriu în order_history.
             */
            $rows = Core::query("SELECT o.`order_id`, o.`order_status_id`, c.`changed_at` FROM (" .
                " SELECT `order_id`, MAX(`changed_at`) AS `changed_at` FROM (" .
                "  SELECT `order_id`, `date_modified` AS `changed_at` FROM `" . DB_PREFIX . "order` WHERE `date_modified` >= '" . $date . "'" .
                "  UNION ALL" .
                "  SELECT `order_id`, `date_added` AS `changed_at` FROM `" . DB_PREFIX . "order_history` WHERE `date_added` >= '" . $date . "'" .
                " ) x GROUP BY `order_id`" .
                ") c INNER JOIN `" . DB_PREFIX . "order` o ON (o.`order_id` = c.`order_id`)" .
                " WHERE o.`store_id` = '" . (int) Core::getStoreID() . "' AND o.`order_status_id` > '0'" .
                " AND (c.`changed_at` > '" . $date . "' OR (c.`changed_at` = '" . $date . "' AND o.`order_id` > '" . (int) $cursor[1] . "'))" .
                " AND c.`changed_at` <= DATE_SUB(NOW(), INTERVAL " . self::BROWSER_MINUTES . " MINUTE)" .
                " ORDER BY c.`changed_at` ASC, o.`order_id` ASC LIMIT " . self::RECONCILE_PAGE)->rows;

            foreach ($rows as $row) {
                if (!self::hasBudget()) {
                    break 2;
                }

                /* ce eșuează intră în lista de reîncercări, cursorul merge mai departe */
                self::syncOrder($logs, (int) $row['order_id'], (int) $row['order_status_id'], $result);

                $cursor = array($row['changed_at'], (int) $row['order_id']);
                $scanned++;
            }

            if (count($rows) < self::RECONCILE_PAGE) {
                break;
            }
        }

        $logs->setMeta($key, $cursor);
    }

    /**
     * Unele module de curierat schimbă `order_status_id` direct, fără istoric și fără
     * date_modified - nu lasă nicio urmă după care să mergem. Pentru comenzile din
     * ultimele SWEEP_DAYS zile comparăm direct statusul cu cel din registru.
     * Doar comenzile cu status trimis cunoscut: nimic nu se retrimite după un upgrade.
     */
    private static function sweepRecent($logs, &$result) {
        $rows = Core::query("SELECT `order_id`, `order_status_id` FROM `" . DB_PREFIX . "order`" .
            " WHERE `store_id` = '" . (int) Core::getStoreID() . "' AND `order_status_id` > '0'" .
            " AND `date_added` >= DATE_SUB(NOW(), INTERVAL " . self::SWEEP_DAYS . " DAY)" .
            " AND `date_added` <= DATE_SUB(NOW(), INTERVAL " . self::BROWSER_MINUTES . " MINUTE)")->rows;

        foreach ($rows as $row) {
            $known = $logs->statusOf($row['order_id']);

            if ($known === null || $known === OrderLogs::UNKNOWN || $known === (int) $row['order_status_id']) {
                continue;
            }

            if (!self::hasBudget()) {
                return;
            }

            self::syncOrder($logs, (int) $row['order_id'], (int) $row['order_status_id'], $result);
        }
    }

    /**
     * @return bool true când toate comenzile de la $from au fost verificate;
     *              altfel endpoint-ul trebuie apelat din nou
     */
    private static function backfill($logs, $from, &$result) {
        $time = strtotime($from);

        if ($time === false) {
            $result['error'] = 'invalid from';
            return true;
        }

        $from = Core::escape(date('Y-m-d H:i:s', $time));
        $last = 0;

        while (self::hasBudget()) {
            $rows = Core::query("SELECT `order_id`, `order_status_id` FROM `" . DB_PREFIX . "order`" .
                " WHERE `store_id` = '" . (int) Core::getStoreID() . "' AND `order_status_id` > '0'" .
                " AND `date_added` >= '" . $from . "' AND `order_id` > '" . $last . "'" .
                " AND `date_added` <= DATE_SUB(NOW(), INTERVAL " . self::BROWSER_MINUTES . " MINUTE)" .
                " ORDER BY `order_id` ASC LIMIT " . self::RECONCILE_PAGE)->rows;

            foreach ($rows as $row) {
                if (!self::hasBudget()) {
                    return false;
                }

                self::syncOrder($logs, (int) $row['order_id'], (int) $row['order_status_id'], $result);
                $last = (int) $row['order_id'];
            }

            if (count($rows) < self::RECONCILE_PAGE) {
                return true;
            }
        }

        return false;
    }

    /* cron-ul de pe server și cel din OpenCart 4 pot porni în același timp */
    private static function lock() {
        $fp = @fopen(MKTR_LIB . 'Storage/reconcile.' . (int) Core::getStoreID() . '.lock', 'c');

        if ($fp === false) {
            return null;
        }

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);
            return false;
        }

        return $fp;
    }

    private static function unlock($fp) {
        if (is_resource($fp)) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    public static function execute() {

        if (Valid::getParam('reconcile') !== null) {
            return array("cron" => array('reconcile' => self::reconcileOrders(Valid::getParam('from'))));
        }

        $data = Data::init();
        $upFeed = $data->update_feed;
        $upReview = $data->update_review;

        if (Config::getStatus() != 0) {

            self::reconcileOrders();

            if (Config::getCronFeed() != 0 && $upFeed < time()) {

                $run = Feed::init();

                $run->execute();

                $fileName = $run->get('fileName') . "." . Valid::getParam('mime-type', Config::defMime);

                Valid::Output($run->get('fileName'), array($run->get('secondName') => $run->execute()));

                FileSystem::writeFile($fileName, Valid::getOutPut());

                $data->update_feed = strtotime("+" . Config::getUpdateFeed() . " hour");
            }

            if (Config::getCronReview() != 0 && $upReview < time()) {

                Reviews::execute();

                $data->update_review = strtotime("+" . Config::getUpdateReview() . " hour");
            }
            $data->save();
        }

        $get = array("cron"=>
                array(
                    'update_feed' => $data->update_feed,
                    'update_review' => $data->update_review
                )
        );
        return $get;
    }
}
