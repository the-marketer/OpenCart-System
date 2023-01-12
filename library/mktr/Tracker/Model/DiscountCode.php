<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Tracker\Model;

use Mktr\Helper\Config;
use Mktr\Helper\Core;
use Mktr\Helper\Model\Coupon;
use Mktr\Helper\Valid;

class DiscountCode
{
    private static $init = null;
    private static $ruleType;
    private static $code = null;

    const PREFIX = 'MKTR-';
    const NAME = "MKTR-%s-%s";
    private static $LENGTH = 10;
    const DESCRIPTION = "Discount Code Generated through TheMarketer API";

    const SYMBOLS_COLLECTION = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    public static function init()
    {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function newCode()
    {

        self::$code = Core::getOcVersion() >= "2.0" ? self::PREFIX : "M-";
        $LENGTH = Core::getOcVersion() >= "2.0" ? self::$LENGTH : 8;
        for ($i = 0, $indexMax = strlen(self::SYMBOLS_COLLECTION) - 1; $i < $LENGTH; ++$i) {
            self::$code .= substr(self::SYMBOLS_COLLECTION, rand(0, $indexMax), 1);
        }

        if (Coupon::checkCode(self::$code))
        {
            self::newCode();
        }

        return self::$code;
    }

    public static function getNewCode() {
        $coupon = array();

        $code = self::newCode();
        $coupon['code'] = $code;

        /* fixed_cart | percent | free_shipping */
        $type = Config::getDiscountRules(Valid::getParam('type'));
        $value = Valid::getParam('value');
        $expiration = Valid::getParam('expiration_date');

        if ($type === 'free_shipping') {
            $coupon['type'] = 'P';
            $coupon['shipping'] = 1;
        } else {
            $coupon['type'] = $type == 'fixed_cart' ? 'F' : 'P';
        }

        $coupon['discount'] = $value;

        $coupon['date_end'] =
            $expiration === null ?
                date('Y-m-d', strtotime('+1 year')) : $expiration;
        $coupon['name'] = self::DESCRIPTION." (".$type."-".$value.( $expiration === null ? '' : '-'.$expiration).")";

        Coupon::addCoupon($coupon);

        return self::$code;
    }

}