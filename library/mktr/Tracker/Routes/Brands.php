<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Valid;
use Mktr\Tracker\Model\Brand;

class Brands
{
    private static $init = null;

    private static $map = array(
        "fileName" => "brands",
        "secondName" => "brand"
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

    public static function execute()
    {
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
            $brand = Brand::getBrand($args);

            if ($stop) {
                $pages = 0;
            } else {
                $pages = $brand->num_rows;
            }

            foreach ($brand->rows as $uniq => $val)
            {
                Brand::selectBrand($uniq);
                $o = array(
                    'id'=> Brand::id(),
                    "name" => Brand::name(),
                    "url" => Brand::getUrl(),
                    // "image_url" => ''
                );

                $img = Brand::getImageUrl();

                if ($img !== null) { $o["image_url"] = $img; }

                $get[] = $o;
            }
            $args['page']++;
        } while (0 < $pages);

/*
        $brandAttribute = Config::getBrandAttribute();
        $get = array();
        foreach ($brandAttribute as $item) {
            $args = array(
                'taxonomy' => $item,
                'order' => 'DESC'
            );

            $cat = get_terms($args);

            if ($cat instanceof WP_Error)
            {
                $args['taxonomy'] = 'pa_'.$args['taxonomy'];
                $cat = get_terms($args);
            }

            foreach ($cat as $k=>$val)
            {

                $get[] = array(
                    "name" => $val->name,
                    'id'=> $val->term_id,
                    "url" => get_term_link($val->term_id)
                    // "image_url" => ''
                );
            }
        }
*/
        return $get;
    }
}
