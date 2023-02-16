<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper\Model;

use Mktr\Helper\Core;

class Module
{
    public static function getTable() {
        return 'module';
    }

    /** @noinspection SqlDialectInspection
     * @noinspection PhpComposerExtensionStubsInspection
     */
    public static function addModule($data, $code = null) {
        if ($code === null) {
            $code = Core::getModuleCode();
        }

        Core::query("INSERT INTO `". DB_PREFIX.self::getTable() ."` SET `name` = '" . Core::escape($data['name']) . "', `code` = '" . Core::escape($code) . "', `setting` = '" . Core::escape(json_encode($data, true)) . "'");

        return Core::lastId();
    }

    /** @noinspection PhpComposerExtensionStubsInspection */
    public static function editModule($module_id, $data) {
        Core::query("UPDATE `". DB_PREFIX.self::getTable() ."` SET `name` = '" . Core::escape($data['name']) . "', `setting` = '" . Core::escape(json_encode($data, true)) . "' WHERE `module_id` = '" . (int) $module_id . "'");
        return true;
    }

    public static function getModule($module_id) {
        /** @noinspection SqlDialectInspection */
        $query = Core::query("SELECT * FROM `". DB_PREFIX.self::getTable() ."` WHERE `module_id` = '". (int)$module_id ."' LIMIT 1");

        if ($query->row) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            return json_decode($query->row['setting'], true);
        } else {
            return array();
        }
    }

    /** @noinspection PhpUnused
     * @noinspection SqlDialectInspection */
    public static function getModulesByCode($code) {
        /** @noinspection SqlDialectInspection */
        $query = Core::query("SELECT * FROM `". DB_PREFIX.self::getTable() ."` WHERE `code` = '". Core::escape($code) ."' ORDER BY `module_id`");

        foreach ($query->rows as $k => $v) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            $query->rows[$k]['setting'] = json_decode($v['setting'], true);
        }

        return $query->rows;
    }

    /** @noinspection SqlDialectInspection
     * @noinspection PhpUnused
     */
    public static function deleteModule($module_id) {
        Core::query("DELETE FROM `". DB_PREFIX . self::getTable() ."` WHERE `module_id` = '" . (int)$module_id . "'");
        Core::query("DELETE FROM `". DB_PREFIX ."layout_module` WHERE `code` LIKE '%." . (int)$module_id . "'");
    }

    /** @noinspection SqlDialectInspection
     * @noinspection PhpUnused
     */
    public static function deleteModulesByCode($code) {
        Core::query("DELETE FROM `". DB_PREFIX . self::getTable() ."` WHERE `code` = '" . Core::escape($code) . "'");
        Core::query("DELETE FROM `". DB_PREFIX ."layout_module` WHERE `code` LIKE '" . Core::escape($code) . "' OR `code` LIKE '" . Core::escape($code . '.%') . "'");
    }
}