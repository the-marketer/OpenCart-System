<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper\Model;

use Mktr\Helper\Core;

class Settings
{
    public static function getTable() {
        return 'setting';
    }

    /** @noinspection PhpComposerExtensionStubsInspection */
    public static function getSettings($code = null, $store_id = null) {
        if ($store_id === null) {
            $store_id = Core::getStoreID();
        }
        if ($code === null) {
            $code = Core::getCode();
        }

        $setting_data = array();

        $query = Core::query("SELECT * FROM `" . DB_PREFIX . self::getTable() . "` WHERE `store_id` = '" . (int) $store_id . "' AND " . (Core::getOcVersion() >= "2.0.1" ? "`code`" : "`group`") . " = '" . Core::escape($code) . "'");

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

    public static function editSetting($data, $store_id = null, $code = null) {
        if ($store_id === null) {
            $store_id = Core::getStoreID();
        }

        if ($code === null) {
            $code = Core::getCode();
        }

        $query = Core::query("SELECT * FROM `" . DB_PREFIX . self::getTable() . "` WHERE `store_id` = '" . (int) $store_id . "' AND " . (Core::getOcVersion() >= "2.0.1" ? "`code`" : "`group`") . " = '" . Core::escape($code) . "'");

        $find = array();

        foreach ($query->rows as $value) {
            $find[$value['key']] = $value['setting_id'];
        }

        foreach ($data as $key => $value) {
            if (substr($key, 0, strlen($code)) != $code) {
                $key = $code . '_' . $key;
            }

            $check = isset($find[$key]);
            if ($check) {
                $q = "UPDATE `" . DB_PREFIX . self::getTable() . "` SET";
            } else {
                $q = "INSERT INTO `" . DB_PREFIX . self::getTable() . "` SET";
            }

            $q .= " `store_id` = '" . (int) $store_id . "', " . (Core::getOcVersion() >= "2.0.1" ? "`code`" : "`group`") . " = '" . Core::escape($code) . "', `key` = '" . Core::escape($key) . "',";

            if (!is_array($value)) {
                $q .= " `value` = '" . Core::escape($value) . "', `serialized` = '0'";
            } else {
                /** @noinspection PhpComposerExtensionStubsInspection */
                $q .= " `value` = '" . Core::escape(json_encode($value, true)) . "', `serialized` = '1'";
            }

            if ($check) {
                $q .= " WHERE `setting_id` = '" . Core::escape($find[$key]) . "'";
            }

            Core::query($q);
        }
        return Core::i();
    }

    public static function getSetting($key, $store_id = null) {
        if ($store_id === null) {
            $store_id = Core::getStoreID();
        }

        $query = Core::query("SELECT * FROM `" . DB_PREFIX . self::getTable() . "` WHERE `store_id` = '" . (int) $store_id . "' AND `key` = '" . Core::escape($key) . "' LIMIT 1");

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

    public static function getSet($key) {
        if (!Core::i()->has($key)) {
            Core::i()->set($key, self::getSetting($key));
        }
        return Core::i()->get($key);
    }

    public static function getSetValue($key) {
        if (!Core::i()->has($key . "_value")) {
            Core::i()->set($key . "_value", self::getSettingValue($key));
        }
        Core::i()->get($key . "_value");
    }

    public static function getSettingValue($key, $store_id = 0) {
        if ($store_id === null) {
            $store_id = Core::getStoreID();
        }

        $query = Core::query("SELECT * FROM `" . DB_PREFIX . self::getTable() . "` WHERE `store_id` = '" . (int) $store_id . "' AND `key` = '" . Core::escape($key) . "' LIMIT 1");

        if ($query->num_rows) {
            if ($query->row['serialized']) {
                /** @noinspection PhpComposerExtensionStubsInspection */
                return json_decode($query->row['value'], true);
            } else {
                return $query->row['value'];
            }
        } else {
            return null;
        }
    }

    /** @noinspection PhpUnused */
    public static function deleteSetting($code, $store_id = null) {
        Core::query("DELETE FROM `" . DB_PREFIX . self::getTable() . "` WHERE " . ($store_id !== null ? "store_id = '" . (int) $store_id . "' AND ":"") . (Core::getOcVersion() >= "2.0.1" ? "`code`" : "`group`") . " = '" . Core::escape($code) . "'");
    }
}
