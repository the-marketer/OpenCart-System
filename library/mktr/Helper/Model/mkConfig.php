<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper\Model;

use Mktr\Helper\Core;
use Mktr\Helper\DB;

class mkConfig extends DB
{
    private static $init = null;

    public $table = 'mktr_config';

    public $primary = 'setting_id';
    public $auto = 'setting_id';

    public $columns = array(
        'setting_id' => 'int(255) NOT NULL AUTO_INCREMENT,',
        'store_id' => "int(11) NOT NULL DEFAULT '0',",
        'key' => 'varchar(255) NOT NULL,',
        'value' => 'text NOT NULL,',
        'serialized' => 'tinyint(1) NOT NULL,'
    );

    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function ins() {
        self::init()->install();
    }

    public static function drop() {
        self::init()->uninstall();
    }

    public static function getTable() {
        return 'mktr_config';
    }

    /** @noinspection PhpComposerExtensionStubsInspection */
    public static function getSettings($store_id = null) {
        if ($store_id === null) {
            $store_id = Core::getStoreID();
        }

        $setting_data = array();

        $query = Core::query("SELECT * FROM `". DB_PREFIX.self::getTable() ."` WHERE `store_id` = '" . (int)$store_id . "'");

        foreach ($query->rows as $result) {
            if (!$result['serialized']) {
                $setting_data[$result['key']] = $result['value'];
            } else {
                $setting_data[$result['key']] = json_decode($result['value'], true);
            }
        }

        return $setting_data;
    }

    public static function saveSetting($key, $value, $store_id = null) {
        return self::editSetting(array($key => $value), $store_id);
    }

    public static function editSetting($data, $store_id = null) {
        if ($store_id === null) {
            $store_id = Core::getStoreID();
        }

        $query = Core::query("SELECT * FROM `". DB_PREFIX.self::getTable() ."` WHERE `store_id` = '" . (int)$store_id . "'");

        $find = array();

        foreach ($query->rows as $value) {
            $find[$value['key']] = $value['setting_id'];
        }

        foreach ($data as $key => $value) {
            $check = isset($find[$key]);
            if ($check) {
                $q = "UPDATE `". DB_PREFIX.self::getTable() ."` SET";
            } else {
                $q = "INSERT INTO `". DB_PREFIX.self::getTable() ."` SET";
            }

            $q .= " `store_id` = '" . (int)$store_id . "', `key` = '" . Core::escape($key) . "',";

            if (!is_array($value)) {
                $q .= " `value` = '" . Core::escape($value) . "', `serialized` = '0'";
            } else {
                /** @noinspection PhpComposerExtensionStubsInspection */
                $q .= " `value` = '" . Core::escape(json_encode($value, true)) . "', `serialized` = '1'";
            }

            if ($check) {
                $q .= " WHERE `setting_id` = '".Core::escape($find[$key])."'";
            }

            Core::query($q);
        }
        return Core::i();
    }

    public static function getSetting($key, $store_id = null) {
        if ($store_id === null) {
            $store_id = Core::getStoreID();
        }

        $query = Core::query("SELECT * FROM `". DB_PREFIX.self::getTable() ."` WHERE `store_id` = '" . (int)$store_id . "' AND `key` = '" . Core::escape($key) . "'");

        if ($query->num_rows) {
            if ($query->row['serialized']) {
                /** @noinspection PhpComposerExtensionStubsInspection */
                $query->row['value'] = json_decode($query->row['value'], true);
            }
            return $query->row;
        } else {
            return null;
        }
    }

    public static function getSettingValue($key, $def = null, $store_id = null) {
        if ($store_id === null) {
            $store_id = Core::getStoreID();
        }

        $query = Core::query("SELECT * FROM `". DB_PREFIX.self::getTable() ."` WHERE `store_id` = '" . (int)$store_id . "' AND `key` = '" . Core::escape($key) . "' LIMIT 1");

        if ($query->num_rows) {
            if ($query->row['serialized']) {
                /** @noinspection PhpComposerExtensionStubsInspection */
                return json_decode($query->row['value'], true);
            } else {
                return $query->row['value'];
            }
        } else {
            return $def;
        }
    }

    /** @noinspection PhpUnused */
    public static function deleteSetting($store_id = null) {
        if ($store_id === null) {
            $store_id = Core::getStoreID();
        }
        Core::query("DELETE FROM `". DB_PREFIX.self::getTable() ."` WHERE store_id = '" . (int)$store_id . "'");
    }
}