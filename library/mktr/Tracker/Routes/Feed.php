<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Core;
use Mktr\Helper\Valid;
use Mktr\Tracker\Model\Product;

class Feed
{
    private static $init = null;

    private static $map = array(
        "fileName" => "products",
        "secondName" => "product"
    );

    public static function init()
    {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function get($f = 'fileName'){
        if (isset(self::$map[$f]))
        {
            return self::$map[$f];
        }
        return null;
    }

    /** @noinspection PhpExpressionAlwaysNullInspection */
    public static function execute() {

        $page = Valid::getParam('page');
        $stop = false;

        if ($page !== null) {
            $stop = true;
        }

        $args = array(
            'page' => $page === null ? 1 : $page,
        );

        $get = array();
        do {
            $products = Product::getProducts($args);
            if ($stop) {
                $pages = 0;
            } else {
                $pages = $products->num_rows;
            }

            foreach ($products->rows as $uniq => $val)
            {
                Product::selectProduct($uniq);

                $oo = array(
                    'id' => Product::id(),
                    'sku' => Product::sku(),
                    'name' => ['@cdata' => Product::name()],
                    'description' => ['@cdata' => Product::description()],
                    'url' => Product::url(),
                    'main_image' => Product::main_image(),
                    'category' => [ '@cdata' => Product::category() ],
                    'brand' => ['@cdata' => Product::brand()],
                    'acquisition_price' => Product::acquisition_price(),
                    'price' => Product::price(),
                    'sale_price' => Product::sale_price(),
                    'sale_price_start_date' => Product::sale_price_start_date(),
                    'sale_price_end_date' => Product::sale_price_end_date(),
                    'availability' => Product::availability(),
                    'stock' => Product::stock(),
                    'media_gallery' => Product::media_gallery(),
                    'variations' => array(
                        'variation' => Product::variation()
                    ),
                    'created_at' => Product::created_at(),
                );

                foreach ($oo as $key =>$val1) {
                    if ($key == 'variations') {
                        if (empty($val1['variation'])) {
                            unset($oo[$key]);
                        }
                    } elseif ($key == 'media_gallery') {
                        if (empty($val1['image'])) {
                            unset($oo[$key]);
                        }
                    } else {
                        if (empty($val1) && $val1 != 0 || $val1 === null) {
                            unset($oo[$key]);
                        }
                    }
                }
                $get[] = $oo;

            }
            $args['page']++;

        } while (0 < $pages);

        return $get;
    }
}