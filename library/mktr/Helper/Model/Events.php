<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper\Model;

use Mktr\Helper\Core;

class Events
{
    public static function getTable() {
        return 'event';
    }

    /** @noinspection SqlDialectInspection */
    public static function addEvent($code, $trigger, $action, $status = 1, $sort_order = 0) {
        Core::query("INSERT INTO `". DB_PREFIX.self::getTable() ."` SET `code` = '" . Core::escape($code) . "', `trigger` = '" . Core::escape($trigger) . "', `action` = '" . Core::escape($action) . "'".
            //" `sort_order` = '" . (int)$sort_order . "',".
            (Core::getOcVersion() >= "2.3" ? ", `status` = '" . (int)$status . "'" : ""));

        return Core::lastId();
    }

    /**
     * @noinspection SqlDialectInspection
     * @noinspection PhpUnused
     */
    public static function deleteEvent($event_id) {
        Core::query("DELETE FROM `". DB_PREFIX.self::getTable() ."` WHERE `event_id` = '" . (int)$event_id . "'");
    }

    /** @noinspection SqlDialectInspection */
    public static function deleteEventByCode($code) {
        Core::query("DELETE FROM `". DB_PREFIX.self::getTable() ."` WHERE `code` = '" . Core::escape($code) . "'");
    }
}