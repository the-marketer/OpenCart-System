<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Model;

use Mktr\Helper\Core;

/**
 * @method static getId()
 * @method static getName()
 * @method static getParentId()
 * @method static id()
 * @method static name()
 */

class Brand
{
    private static $init = null;
    private static $asset = null;
    private static $data;

    private static $valueNames = array(
        'id' => 'manufacturer_id',
        'main_image' => 'image'
    );

    private static $Args = array(
        'limit' => 250,
        'page' => 1
    );
    private static $brand = null;
    /**
     * @var string
     */
    private static $Link = null;

    public static function init()
    {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function __callStatic($name, $arguments)
    {
        return self::getValue($name);
    }

    public function __call($name, $arguments)
    {
        return self::getValue($name);
    }

    public static function getValue($name)
    {
        if (self::$asset == null) {
            self::getById();
        }

        if (isset(self::$valueNames[$name])) {
            $v = self::$valueNames[$name];
            self::$data[$name] = self::$asset[$v];
            return self::$asset[$v];
        }

        if (isset(self::$asset[$name])) {
            return self::$asset[$name];
        }
        return null;
    }

    public static function imageUrl($i) {
        if (self::$Link === null) {
            if (Core::request()->server['HTTPS']) {
                self::$Link = Core::ocConfig('config_ssl') . 'image/';
            } else {
                self::$Link = Core::ocConfig('config_url') . 'image/';
            }
        }
        return self::$Link . $i;
    }

    public static function getImageUrl() {
        $img = self::getValue('image');
        if (!empty($img)) {
            return self::imageUrl($img);
        }
        return $img;
    }

    public static function getUrl() {
        if (!isset(self::$data['getUrl'])) {
            self::$data['getUrl'] = Core::url()->link('product/manufacturer/info', 'manufacturer_id=' . self::id());
            if (strpos(self::$data['getUrl'], 'manufacturer_id=') !== false){
                self::$data['getUrl'] = str_replace('&amp;','&', self::$data['getUrl']);
            }
        }

        return self::$data['getUrl'];
    }

    public static function getBrand($arg = array()) {
        self::$Args = array_merge(self::$Args, $arg);

        $page = self::$Args['page'];
        $limit = self::$Args['limit'];

        $offset = (($page - 1) * $limit);

        self::$brand = Core::query(
            "SELECT DISTINCT * FROM " . DB_PREFIX . "manufacturer m" .
            " LEFT JOIN " . DB_PREFIX . "manufacturer_to_store ms ON (m.manufacturer_id = ms.manufacturer_id)" .
            " WHERE" .
            " ms.store_id = '" . (int) Core::ocConfig('config_store_id') . "'" .
            " ORDER BY m.`manufacturer_id` LIMIT " . $limit .
            " OFFSET " . $offset);

        return self::$brand;
    }

    public static function selectBrand($s) {
        self::$data = array();
        self::$asset = self::$brand->rows[$s];

        return self::init();
    }

    public static function getAsset() {
        return self::$asset;
    }
}
