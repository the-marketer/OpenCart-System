<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

class Install
{
    private static $paths = array(
        /*'2.3' => array(
            'controller' => 'controller/extension/module/',
            'language' => 'language/en-gb/extension/module/'
        ),
        '1.5' => array(
            'controller' => 'controller/module/',
            'language' => 'language/english/module/'
        )*/
        '2.3' => array(
            'controller' => 'extension/module',
            'language' => 'en-gb'
        ),
        '1.5' => array(
            'controller' => 'module',
            'language' => 'english'
        )
    );

    private static $data = array();

    const CRON_CODE = 'mktr_reconcile';

    /**
     * OpenCart 4 are Cron Jobs proprii (tabelul `cron`, rulat de ruta cron/cron).
     * Ne înscriem acolo ca reconcilierea comenzilor să meargă și fără o linie
     * separată în crontab. Se poate apela oricând: dacă jobul există, doar îl corectăm.
     */
    public static function addCron() {
        if (!defined('VERSION') || version_compare(VERSION, '4.0.0.0', '<')) {
            return;
        }

        /* 4.0.0 - 4.0.1 despart metoda cu "|", de la 4.0.2 cu "." */
        $action = Core::getLink() . (version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|') . 'cron';

        $query = Core::query("SELECT `cron_id`, `action` FROM `" . DB_PREFIX . "cron` WHERE `code` = '" . self::CRON_CODE . "'");

        if ($query->num_rows) {
            if ($query->row['action'] !== $action) {
                Core::query("UPDATE `" . DB_PREFIX . "cron` SET `action` = '" . Core::escape($action) . "' WHERE `cron_id` = '" . (int) $query->row['cron_id'] . "'");
            }
            return;
        }

        Core::query("INSERT INTO `" . DB_PREFIX . "cron` SET `code` = '" . self::CRON_CODE . "'," .
            " `description` = 'TheMarketer - reconcile orders', `cycle` = 'hour'," .
            " `action` = '" . Core::escape($action) . "', `status` = '1', `date_added` = NOW(), `date_modified` = NOW()");
    }

    public static function deleteCron() {
        if (!defined('VERSION') || version_compare(VERSION, '4.0.0.0', '<')) {
            return;
        }

        Core::query("DELETE FROM `" . DB_PREFIX . "cron` WHERE `code` = '" . self::CRON_CODE . "'");
    }

    public static function getPaths() {
        if (!isset(self::$data['path'])) {
            foreach (self::$paths as $key => $val) {
                if (!defined('VERSION') || $key <= VERSION) {
                    self::$data['path'] = $val;
                    break;
                }
            }
        }
        return self::$data['path'];
    }
}
