<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper\Model;

use Mktr\Helper\Core;

class Store
{
    public static function getTable() {
        return 'store';
    }

    public static function getStores($id = null, $fresh = true) {

        $stores = $fresh === true ? null : Core::cache('getStores');

        if (!$stores) {
            if (Core::getOcVersion() >= "4") {

                if (defined('HTTP_CATALOG')) {
                    $url = basename(DIR_TEMPLATE) == 'template' ? HTTP_CATALOG : HTTP_SERVER;
                } else {
                    $url = HTTP_SERVER;
                }
            } else {
                $url = basename(DIR_TEMPLATE) == 'template' ? HTTPS_CATALOG : HTTPS_SERVER;
            }

            $stores = array(
                0 => array(
                    "store_id" => 0,
                    "name" => Core::ocConfig('config_name'),
                    "url" => $url,
                    "ssl" => $url
                )
            );

            $query = Core::query("SELECT * FROM `" . DB_PREFIX . self::getTable() . "` ORDER BY store_id");

            foreach ($query->rows as $value) {
                $value['store_id'] = (int) $value['store_id'];
                $stores[$value['store_id']] = $value;
            }

            Core::setCache('getStores', $stores);
        }

        return $id === null ? $stores : $stores[$id];
    }
}
