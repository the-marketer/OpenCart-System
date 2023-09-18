<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Model;

use Mktr\Helper\Core;
use Mktr\Helper\Config;
use Mktr\Helper\Valid;
use Mktr\Tracker\Events;

/**
 * @method static id()
 * @method static sku()
 * @method static name()
 * @method static description()
 * @method static url()
 * @method static main_image()
 * @method static image()
 * @method static category()
 * @method static brand()
 * @method static acquisition_price()
 * @method static price()
 * @method static sale_price()
 * @method static sale_price_start_date()
 * @method static sale_price_end_date()
 * @method static availability()
 * @method static stock()
 * @method static media_gallery()
 * @method static variation()
 * @method static created_at()
 * @method static tax_class_id()
 * @method static special_price()
 * @method static regular_price()
 * @method static date_added()
 */

class Product
{
    private static $init = null;
    private static $asset = null;
    private static $data = array();

    private static $valueNames = array(
        'id' => 'product_id',
        'sku' => 'getSku',
        'name' => 'name',
        'description' => 'description',
        'url' => 'getUrl',
        'main_image' => 'getImage',
        'image' => 'image',
        'category' => 'getCategory',
        'brand' => 'getBrand',
        'acquisition_price' => 'getAcquisitionPrice',
        'price' => 'getPrice',
        'regular_price' => 'price',
        'sale_price' => 'getSalePrice',
        'special_price' => 'special',
        'sale_price_start_date' => 'getSalePriceStartDate',
        'sale_price_end_date' => 'getSalePriceEndDate',
        'availability' => 'getStockStatus',
        'stock' => 'getQuantity',
        'media_gallery' => 'getMediaGallery',
        'variation' => 'getVariation',
        'created_at' => 'getCreateAt',
        'tax_class_id' => 'tax_class_id'
    );

    private static $productsArgs = array(
        'limit' => 250,
        'page' => 1
    );

    private static $products = null;

    private static $Link = null;

    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public function __call($n, $a) {
        return self::getValue($n);
    }

    public static function __callStatic($n, $a) {
        return self::getValue($n);
    }

    public static function getValue($n) {
        if (isset(self::$data[$n])) {
            return self::$data[$n];
        }

        if (self::$asset == null) {
            self::getById();
        }

        if (isset(self::$valueNames[$n])) {
            if (isset(self::$asset[self::$valueNames[$n]])) {
                self::$data[$n] = self::$asset[self::$valueNames[$n]];
            } else {
                self::$data[$n] = self::{self::$valueNames[$n]}();
            }

            return self::$data[$n];
        }

        if (isset(self::$asset[$n])) {
            self::$data[$n] = self::$asset[$n];
            return self::$data[$n];
        }

        return null;
    }

    public static function getCreateAt() {
        return date(Config::$dateFormat, strtotime(self::date_added()));
    }

    public static function getProduct($product_id) {

        /** TODO: (int)$product_id |  AND p.`status` = '1' **/
        /** TODO: pd.`language_id` = '" . (int)Core::ocConfig('config_language_id') . "' AND p.`status` = '1' **/

        $query = Core::query("SELECT DISTINCT *, pd.`name` AS name, p.`image`, m.`name` AS manufacturer, (SELECT `price` FROM `" . DB_PREFIX . "product_discount` pd2 WHERE pd2.`product_id` = p.`product_id` AND pd2.`customer_group_id` = '" . (int) Core::ocConfig('config_customer_group_id') . "' AND pd2.`quantity` = '1' AND ((pd2.`date_start` = '0000-00-00' OR pd2.`date_start` < NOW()) AND (pd2.`date_end` = '0000-00-00' OR pd2.`date_end` > NOW())) ORDER BY pd2.`priority` ASC, pd2.`price` ASC LIMIT 1) AS `discount`, (SELECT `price` FROM `" . DB_PREFIX . "product_special` ps WHERE ps.`product_id` = p.`product_id` AND ps.`customer_group_id` = '" . (int) Core::ocConfig('config_customer_group_id') . "' AND ((ps.`date_start` = '0000-00-00' OR ps.`date_start` < NOW()) AND (ps.`date_end` = '0000-00-00' OR ps.`date_end` > NOW())) ORDER BY ps.`priority` ASC, ps.`price` ASC LIMIT 1) AS `special`, (SELECT `points` FROM `" . DB_PREFIX . "product_reward` pr WHERE pr.`product_id` = p.`product_id` AND pr.`customer_group_id` = '" . (int) Core::ocConfig('config_customer_group_id') . "') AS `reward`, (SELECT ss.`name` FROM `" . DB_PREFIX . "stock_status` ss WHERE ss.`stock_status_id` = p.`stock_status_id` AND ss.`language_id` = '" . (int) Core::ocConfig('config_language_id') . "') AS `stock_status`, (SELECT wcd.`unit` FROM `" . DB_PREFIX . "weight_class_description` wcd WHERE p.`weight_class_id` = wcd.`weight_class_id` AND wcd.`language_id` = '" . (int) Core::ocConfig('config_language_id') . "') AS `weight_class`, (SELECT lcd.`unit` FROM `" . DB_PREFIX . "length_class_description` lcd WHERE p.`length_class_id` = lcd.`length_class_id` AND lcd.`language_id` = '" . (int) Core::ocConfig('config_language_id') . "') AS length_class, (SELECT AVG(`rating`) AS `total` FROM `" . DB_PREFIX . "review` r1 WHERE r1.`product_id` = p.`product_id` AND r1.`status` = '1' GROUP BY r1.`product_id`) AS `rating`, (SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "review` r2 WHERE r2.`product_id` = p.`product_id` AND r2.`status` = '1' GROUP BY r2.`product_id`) AS `reviews`, p.`sort_order` FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.`product_id` = pd.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.`product_id` = p2s.`product_id`) LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON (p.`manufacturer_id` = m.`manufacturer_id`) WHERE p.`product_id` = '" . (int) $product_id . "' AND pd.`language_id` = '" . (int) Core::ocConfig('config_language_id') . "' AND p.`date_available` <= NOW() AND p2s.`store_id` = '" . (int) Core::ocConfig('config_store_id') . "'");

        if ($query->num_rows) {
            $product_data = $query->row;

            if (isset($query->row['variant'])) {
                $product_data['variant'] = (array) json_decode($query->row['variant'], true);
            }

            $product_data['price'] = isset($query->row['discount']) ? $query->row['discount'] : $query->row['price'];

            return $product_data;
        } else {
            return array();
        }
    }

    public static function getProducts($arg = array()) {

        $arg['limit'] = Valid::getParam('limit', self::$productsArgs['limit']);

        self::$productsArgs = array_merge(self::$productsArgs, $arg);


        $page = self::$productsArgs['page'];
        $limit = self::$productsArgs['limit'];

        $offset = (($page - 1) * $limit);

        self::$products = 
        Core::query(
            "SELECT DISTINCT *, pd.`name` AS name, p.`image`, m.`name` AS manufacturer," .
            " (SELECT `price` FROM `" . DB_PREFIX . "product_discount` pd2 WHERE pd2.`product_id` = p.`product_id` AND pd2.`customer_group_id` = '" . (int) Core::ocConfig('config_customer_group_id') . "' AND pd2.`quantity` = '1' AND ((pd2.`date_start` = '0000-00-00' OR pd2.`date_start` < NOW()) AND (pd2.`date_end` = '0000-00-00' OR pd2.`date_end` > NOW())) ORDER BY pd2.`priority` ASC, pd2.`price` ASC LIMIT 1) AS `discount`," .
            " (SELECT `price` FROM `" . DB_PREFIX . "product_special` ps WHERE ps.`product_id` = p.`product_id` AND ps.`customer_group_id` = '" . (int) Core::ocConfig('config_customer_group_id') . "' AND ((ps.`date_start` = '0000-00-00' OR ps.`date_start` < NOW()) AND (ps.`date_end` = '0000-00-00' OR ps.`date_end` > NOW())) ORDER BY ps.`priority` ASC, ps.`price` ASC LIMIT 1) AS `special`," .
            " (SELECT `points` FROM `" . DB_PREFIX . "product_reward` pr WHERE pr.`product_id` = p.`product_id` AND pr.`customer_group_id` = '" . (int) Core::ocConfig('config_customer_group_id') . "') AS `reward`, (SELECT ss.`name` FROM `" . DB_PREFIX . "stock_status` ss WHERE ss.`stock_status_id` = p.`stock_status_id` AND ss.`language_id` = '" . (int) Core::ocConfig('config_language_id') . "') AS `stock_status`," .
            " (SELECT wcd.`unit` FROM `" . DB_PREFIX . "weight_class_description` wcd WHERE p.`weight_class_id` = wcd.`weight_class_id` AND wcd.`language_id` = '" . (int) Core::ocConfig('config_language_id') . "') AS `weight_class`, (SELECT lcd.`unit` FROM `" . DB_PREFIX . "length_class_description` lcd WHERE p.`length_class_id` = lcd.`length_class_id` AND lcd.`language_id` = '" . (int) Core::ocConfig('config_language_id') . "') AS length_class," .
            " (SELECT AVG(`rating`) AS `total` FROM `" . DB_PREFIX . "review` r1 WHERE r1.`product_id` = p.`product_id` AND r1.`status` = '1' GROUP BY r1.`product_id`) AS `rating`," .
            " (SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "review` r2 WHERE r2.`product_id` = p.`product_id` AND r2.`status` = '1' GROUP BY r2.`product_id`) AS `reviews`," .
            " p.`sort_order` FROM `" . DB_PREFIX . "product` p LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.`product_id` = pd.`product_id`) LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON" .
            " (p.`product_id` = p2s.`product_id` AND p.`status` = '1') LEFT JOIN `" . DB_PREFIX . "manufacturer` m ON (p.`manufacturer_id` = m.`manufacturer_id`) WHERE pd.`language_id` = '" . (int) Core::ocConfig('config_language_id') . "' AND p.`date_available` <= NOW() AND p2s.`store_id` = '" . (int) Core::ocConfig('config_store_id') . "'" .
            " ORDER BY p.`product_id` LIMIT " . $limit .
            " OFFSET " . $offset);

        return self::$products;
    }

    public static function selectProduct($s) {
        self::$data = array();
        self::$asset = self::$products->rows[$s];

        if (isset(self::$asset['variant'])) {
            self::$asset['variant'] = (array) json_decode(self::$asset['variant'], true);
        }

        self::$asset['price'] = isset(self::$asset['discount']) ? self::$asset['discount'] : self::$asset['price'];


        return self::init();
    }

    public static function getById($id = null) {
        if ($id === null && isset(Core::request()->get['product_id'])) {
            $id = Core::request()->get['product_id'];
        }

        if ($id !== null) {
            self::$data = array();
            self::$asset = self::getProduct($id);
        }

        return self::init();
    }

    /** @noinspection PhpUnused */
    public static function getStockStatus() {
        switch (self::$asset['stock_status']) {
            case 'Out Of Stock':
                return 0;
            case 'In Stock':
                return 1;
            default:
                return 2;
        }
    }

    /** @noinspection PhpUnused */
    public static function getQuantity() {
        return self::$asset['quantity'] < 0 ? Config::getDefaultStock() : self::$asset['quantity'];
    }

    public static function getSku() {
        return empty(self::$asset['sku']) ? self::id() : self::$asset['sku'];
    }

    public static function getBrand() {
        if (!isset(self::$data['brand'])) {
            if (empty(self::$asset['manufacturer'])) {
                /** TODO: Attributes **/
                if (!isset(self::$data['ProductAttributes'])) {
                    if (Core::getOcVersion() >= "4") {
                        self::$data['ProductAttributes'] = Core::ocModel('catalog/product')->getAttributes(self::id());
                    } else {
                        self::$data['ProductAttributes'] = Core::ocModel('catalog/product')->getProductAttributes(self::id());
                    }
                }

                foreach (self::$data['ProductAttributes'] as $vv) {
                    foreach ($vv['attribute'] as $vvv) {
                        if (in_array($vvv['name'], Config::getBrandAttribute())) {
                            self::$data['brand'] = $vvv['text'];
                            break;
                        }
                    }
                }
            } else {
                self::$data['brand'] = self::$asset['manufacturer'];
            }
            if (!isset(self::$data['brand'])) {
                self::$data['brand'] = 'N/A';
            }
        }
        return self::$data['brand'];
    }

    /** @noinspection PhpUnused */
    public static function getSalePriceStartDate() {
        if (!isset(self::$data['sale_price_start_date'])) {
            self::getSalePriceDate();
        }
        return self::$data['sale_price_start_date'];
    }

    /** @noinspection PhpUnused */
    public static function getSalePriceEndDate() {
        if (!isset(self::$data['sale_price_end_date'])) {
            self::getSalePriceDate();
        }
        return self::$data['sale_price_end_date'];
    }

    public static function getSalePriceDate() {
        $query = Core::query("SELECT `price`,`date_start`,`date_end`  FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) self::id() . "' AND customer_group_id = '" . (int) Core::ocConfig('config_customer_group_id') . "' AND ((date_start = '0000-00-00' OR date_start < NOW()) AND (date_end = '0000-00-00' OR date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");

        if ($query->num_rows) {
            self::$data['sale_price_start_date'] = $query->row['date_start'] == '0000-00-00' ? null : Valid::correctDate($query->row['date_start']);
            self::$data['sale_price_end_date'] = $query->row['date_end'] == '0000-00-00' ? null : Valid::correctDate($query->row['date_end']);
        } else {
            self::$data['sale_price_start_date'] = null;
            self::$data['sale_price_end_date'] = null;
        }
    }

    public static function getCartItem($key) {
        $query = Core::query("SELECT * FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int) $key . "' LIMIT 1");
        return $query->row;
    }

    public static function getUrl() {
        if (!isset(self::$data['getUrl'])) {
            self::$data['getUrl'] = Core::url()->link('product/product', 'product_id=' . self::id());

            if (strpos(self::$data['getUrl'], 'product_id=') !== false){
                self::$data['getUrl'] = str_replace('&amp;', '&',self::$data['getUrl']);
            }

            self::$data['getUrl'] = str_replace(" ", "%20", self::$data['getUrl']);
        }

        return self::$data['getUrl'];
    }

    public static function getImage() {
        return self::imageUrl(self::getValue('image'));
    }

    public static function getCategory(){
        return Events::buildMultiCategory(
            Core::ocModel('catalog/product')->getCategories(self::id())
        );
    }

    public static function getAcquisitionPrice() {
        return 0;
    }

    public static function dd() {
        if (isset($_COOKIE['EAX'])) {
            echo '<pre>';
            foreach (func_get_args() as $variable) {
                var_dump($variable);
            } echo '</pre>';
            exit;
        }
        return true;
    }

    public static function buildCombinations($attributes, $prefix = []) {
        $combinations = [];
        $attributeKey = key($attributes);
        $attributeValues = array_shift($attributes);
        foreach ($attributeValues as $key => $value) {
            $newPrefix = array_merge($prefix, [$attributeKey => $value]);
            if (empty($attributes)) {
                $combinations[] = $newPrefix;
            } else {
                $combinations = array_merge($combinations, self::buildCombinations($attributes, $newPrefix));
            }
        }
        return $combinations;
    }

    /** @noinspection PhpUnused */
    public static function getVariation($byID = null) {
        if (!isset(self::$data['getVariation']) || isset(self::$data['getVariation']) && $byID !== null && self::$data['byID'] !== $byID) {
            $added = [];
            if ($byID === null) {
                $byID = false;
            }

            if (Core::getOcVersion() >= "4") {
                $productOptions = Core::ocModel('catalog/product')->getOptions(self::id());
            } else {
                $productOptions = Core::ocModel('catalog/product')->getProductOptions(self::id());
            }

            if (Core::getOcVersion() >= '2.0') {
                $opt = 'product_option_value';
            } else {
                $opt = 'option_value';
            }
            $newAttr = [];

            // self::dd($productOptions);

            foreach ($productOptions as $key => $value) {
                if (in_array($value['type'], ['radio', 'checkbox', 'select'])) {
                    $parent = false;
                    $parentDATA = false;
                    if (isset($value['pcop_front'][0]['parent_option_id'])) {
                        $parent = $value['pcop_front'][0]['parent_option_id'];
                        $parentDATA = $value['pcop_front'][0]['values'];
                    }
                    foreach ($value[$opt] as $key1 => $value1) { 
                        $newAttr['o'.$value['product_option_id']][] = [
                            'parent_id' => $parent,
                            'parentDATA' => $parentDATA,
                            'pOption' => $value['product_option_id'],
                            'option_id' => $value['option_id'],
                            'option_name' => $value['name'],
                            'type' => $value['type'],
                            'price' => $value1['price'],
                            'price_prefix' => $value1['price_prefix'],
                            'quantity' => $value1['quantity'],
                            'name' => $value1['name'],
                            'product_option_value_id' => $value1['product_option_value_id'],
                            'option_value_id' => $value1['option_value_id']
                        ];
                    }
                }
            }
            if (count($newAttr) > 15) {
                $newAttr = array_slice($newAttr, 0, 15);
            }
            // self::dd($newAttr);
            if (!empty($newAttr) && is_array($newAttr)) {
                $products = self::buildCombinations($newAttr);
                foreach ($products as $key => $value) {
                    foreach ($value as $key1 => $value1) {
                        if ($value1['parent_id'] !== false &&
                            !in_array($value['o'.$value1['parent_id']]['product_option_value_id'], $value1['parentDATA'])) {
                            unset($products[$key][$key1]);
                        }
                    }
                }
            }

            $variation = array();
            $colorAttr = Config::getColorAttribute();
            $sizeAttr = Config::getSizeAttribute();

            foreach ($products as $k => $val) {
                if (empty($val)) { continue; }
                /** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */
                $newVariation = array(
                    'id' => array(self::id()),
                    'sku' => array(self::sku()),
                    'acquisition_price' => 0,
                    'price' => self::regular_price(),
                    'sale_price' => self::special_price(),
                    'availability' => 0,
                    'stock' => 0,
                    'size' => null,
                    'color' => null
                );
                if ($newVariation['sale_price'] == null) {
                    $newVariation['sale_price'] = $newVariation['price'];
                }
                $skip = false;
                foreach ($val as $val0) {
                    if (in_array($val0['name'], $newVariation['sku']) || $skip) {
                        $skip = true;
                        continue;
                    }
                    if ($val0['parent_id'] === false || in_array('o'.$val0['parent_id'], array_keys($val))) {
                        $id = $val0['pOption'];
                        $name = $val0['name'];

                        if (isset($colorAttr[strtolower($name)])) {
                            $newVariation['color'] = $val0['name'];
                        } else if (isset($sizeAttr[strtolower($name)])) {
                            $newVariation['size'] = $val0['name'];
                        }

                        $newVariation['id'][$val0['pOption']] = $val0['pOption'];
                        $newVariation['id'][$val0['product_option_value_id']] = $val0['product_option_value_id'];

                        // $newVariation['sku'][] = $val0['pOption'];
                        $newVariation['sku'][] = $val0['name'];

                        if ($val0['price_prefix'] == '+') {
                            // $newVariation['price'] = $newVariation['price'] + $val0['price'];
                            $newVariation['sale_price'] = $newVariation['sale_price'] + $val0['price'];
                        } else {
                            // $newVariation['price'] = $newVariation['price'] - $val0['price'];
                            $newVariation['sale_price'] = $newVariation['sale_price'] - $val0['price'];
                        }

                        if ($newVariation['price'] < $newVariation['sale_price']) {
                            $newVariation['price'] = $newVariation['sale_price'];
                        }

                        if ($newVariation['stock'] === 0) {
                            $newVariation['stock'] = $val0['quantity'];
                        } else {
                            $newVariation['stock'] = min($newVariation['stock'], $val0['quantity']);
                        }
                    }
                }
                if ($skip) {
                    continue;
                }

                $newVariation['id'] = implode(Config::$vSeparator, $newVariation['id']);

                if (in_array($newVariation['id'], $added)) {
                    continue;
                }

                $newVariation['sku'] = implode(Config::$vSeparator, $newVariation['sku']);
                $newVariation['sku'] = str_replace(' ', '_', $newVariation['sku']);

                $added[] = $newVariation['id'];

                $newVariation['price'] = Core::i()->tax->calculate(
                    (float) $newVariation['price'],
                    self::tax_class_id(),
                    Core::i()->config->get('config_tax')
                );

                $newVariation['sale_price'] = Core::i()->tax->calculate(
                    (float) $newVariation['sale_price'],
                    self::tax_class_id(),
                    Core::i()->config->get('config_tax')
                );
                $newVariation['price'] = Valid::digit2($newVariation['price']);

                if (empty($newVariation['sale_price'])) {
                    $newVariation['sale_price'] = $newVariation['price'];
                } else {
                    $newVariation['sale_price'] = Valid::digit2($newVariation['sale_price']);
                }

                $newVariation['availability'] = self::availability();

                if (empty($newVariation['size'])) {
                    unset($newVariation['size']);
                }

                if (empty($newVariation['color'])) {
                    unset($newVariation['color']);
                }

                if ($byID) {
                    $variation[$newVariation['id']] = $newVariation;
                } else {
                    $variation[] = $newVariation;
                }
            }

            self::$data['byID'] = $byID;
            self::$data['getVariation'] = $variation;
        }

        return self::$data['getVariation'];
    }

    public static function searchForVariantId($id) {
        $variants = self::getVariation();
        $found = array_search($id, Core::searchIn($variants, 'id'));
        return isset($variants[$found]) ? $variants[$found] : null;
    }

    public static function getPrice($check = false) {
        $p = self::regular_price();

        $p = Core::i()->tax->calculate(
            (float) $p,
            self::tax_class_id(),
            Core::i()->config->get('config_tax')
        );

        $r = $check === true || !empty($p) ? $p : self::getSalePrice(true);

        return Valid::digit2($r);
    }

    public static function getSalePrice($check = false) {
        $p = self::special_price();
        if ($p !== null) {
            $p = Core::i()->tax->calculate(
                (float) $p,
                self::tax_class_id(),
                Core::i()->config->get('config_tax')
            );
        } else {
            $p = self::getPrice(true);
        }

        $r = $check === true || !empty($p) ? $p : self::getPrice(true);
        return Valid::digit2($r);
    }

    public static function imageUrl($i) {
        if (self::$Link === null) {
            self::$Link = str_replace(array("index.php?route=", " "), array("", "%20"), Core::url()->link("image/"));
            /*if (Core::request()->server['HTTPS']) {
                self::$Link = Core::ocConfig('config_ssl') . 'image/';
            } else {
                self::$Link = Core::ocConfig('config_url') . 'image/';
            }*/
        }
        return self::$Link . str_replace(" ", "%20", $i);
    }

    /** @noinspection PhpUnused */
    public static function getMediaGallery() {
        $list = array();
        if (Core::getOcVersion() >= "4") {
            $l = Core::ocModel('catalog/product')->getImages(self::id());
        } else {
            $l = Core::ocModel('catalog/product')->getProductImages(self::id());
        }

        foreach ($l as $v) {
            $list[] = self::imageUrl($v['image']);
        }

        return $list;
    }

    public static function toArray() {
        $data = array();

        foreach (self::$valueNames as $key=>$value) {
            $data[$key] = self::$key();
        }

        return $data;
    }
}
