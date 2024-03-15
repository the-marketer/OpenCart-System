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
 */

class Category
{
    private static $init = null;
    private static $asset = null;
    private static $data;

    private static $valueNames = array(
        'getId' => 'category_id',
        'getName' => 'name',
        'getParentId' => 'parent_id'
    );

    private static $categoryArgs = array(
        'limit' => 250,
        'page' => 1
    );
    private static $category = null;
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
        if (isset(self::$valueNames[$name]) && isset(self::$asset[self::$valueNames[$name]])) {
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

    public static function getById($id = null)
    {
        if ($id == null && isset(Core::request()->get['path']))
        {
            $ct = explode('_', Core::request()->get['path']);

            $id = end($ct);
        }

        self::$asset = Core::ocModel('catalog/category')->getCategory($id);

        return self::init();
    }

    public static function getUrl() {
        if (!isset(self::$data['getUrl'])) {
            $ids = array();

            if (self::getParentId() != 0) {
                $ids[] = self::getParentId();
            }

            $ids[] = self::getId();

            self::$data['getUrl'] = Core::url()->link('product/category', 'path=' . implode('_',$ids));
            if (strpos(self::$data['getUrl'], 'path=') !== false){
                self::$data['getUrl'] = str_replace('&amp;','&', self::$data['getUrl']);
            }
        }

        return self::$data['getUrl'];
    }

    public static function getCategory($arg = array()) {
        self::$categoryArgs = array_merge(self::$categoryArgs, $arg);

        $page = self::$categoryArgs['page'];
        $limit = self::$categoryArgs['limit'];

        $offset = (($page - 1) * $limit);

        self::$category = Core::query(
            "SELECT DISTINCT * FROM " . DB_PREFIX . "category c" .
            " LEFT JOIN " . DB_PREFIX . "category_description cd ON (c.category_id = cd.category_id)" .
            " LEFT JOIN " . DB_PREFIX . "category_to_store c2s ON (c.category_id = c2s.category_id)" .
            " WHERE" .
            " cd.language_id = '" . (int) Core::ocConfig('config_language_id') . "'" .
            " AND c2s.store_id = '" . (int) Core::ocConfig('config_store_id') . "'" .
            " AND c.status = '1'" .
            " ORDER BY cd.`category_id` LIMIT " . $limit .
            " OFFSET " . $offset);

        return self::$category;
    }

    public static function selectCategory($s) {
        self::$data = array();
        self::$asset = self::$category->rows[$s];

        return self::init();
    }

    public static function getAsset() {
        return self::$asset;
    }
}
