<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Routes;

use Mktr\Helper\Core;
use Mktr\Helper\Valid;
use Mktr\Tracker\Events;

class Category
{
    private static $init = null;

    private static $map = array(
        "fileName" => "categories",
        "secondName" => "category"
    );

    private static $cat = array();

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
            $category = \Mktr\Tracker\Model\Category::getCategory($args);

            if ($stop) {
                $pages = 0;
            } else {
                $pages = $category->num_rows;
            }

            foreach ($category->rows as $uniq => $val)
            {
                \Mktr\Tracker\Model\Category::selectCategory($uniq);

                $o = array(
                    'id'=> \Mktr\Tracker\Model\Category::getId(),
                    "name" => \Mktr\Tracker\Model\Category::getName(),
                    "url" => \Mktr\Tracker\Model\Category::getUrl(),
                    "hierarchy" => Events::buildCategory(\Mktr\Tracker\Model\Category::init())
                );

                $img = \Mktr\Tracker\Model\Category::getImageUrl();

                if ($img !== null) { $o["image_url"] = $img; }

                $get[] = $o;
            }
            $args['page']++;

        } while (0 < $pages);

        return $get;
    }
}
