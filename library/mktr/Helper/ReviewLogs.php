<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

class ReviewLogs extends NoSql
{
    private static $init = null;
    protected $file = "reviews.json";

    public static function i() {
        if (self::$init === null) {
            self::$init = new self();
        }
        return self::$init;
    }
}
