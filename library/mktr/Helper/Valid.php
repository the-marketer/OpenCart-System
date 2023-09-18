<?php /** @noinspection PhpFullyQualifiedNameUsageInspection */

/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

class Valid
{
    private static $init = null;
    private static $params = null;
    private static $error = null;

    private static $mime = array(
        'xml' => 'application/xhtml+xml',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'csv' => 'text/csv'
    );

    private static $getOut = null;

    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public static function getParam($name = null, $def = null) {
        if (self::$params == null)
        {
            self::$params = $_GET;
        }

        if ($name === null)
        {
            return self::$params;
        }

        if (isset(self::$params[$name]))
        {
            return self::$params[$name];
        }
        if ($def !== null) {
            self::$params[$name] = $def;
        }
        return $def;
    }

    /** @noinspection PhpUnused */
    public static function setParam($name, $value) {
        if (self::$params == null)
        {
            self::$params = $_GET;
        }

        self::$params[$name] = $value;

        return self::init();
    }

    /** @noinspection PhpUnused */
    public static function validateTelephone($phone) {
        return preg_replace("/\D/", "", $phone);
    }


    public static function validateDate($date, $format = 'Y-m-d') {
        // Config::$dateFormat = $format;
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /** @noinspection PhpUnused */
    public static function correctDate($date = null, $format = "Y-m-d H:i") {
        return $date !== null ? date($format, strtotime($date)) : $date;
    }

    /** @noinspection PhpUnused
     * @noinspection PhpRedundantOptionalArgumentInspection
     */
    public static function digit2($num) {
        return number_format((float) $num, 2, '.', '');
    }

    public static function check($checkParam = null) {
        if (self::$params == null)
        {
            self::$params = $_GET;
        }

        if ($checkParam === null)
        {
            return null;
        }

        self::$error = null;

        foreach ($checkParam as $k=>$v)
        {
            if ($v !== null)
            {
                $check = explode("|", $v);
                foreach ($check as $do)
                {
                    if (self::$error === null) {
                        switch ($do)
                        {
                            case "Required":
                                if (!isset(self::$params[$k]))
                                {
                                    self::$error = "Missing Parameter " . $k;
                                }
                                break;
                            case "DateCheck":
                                if (isset(self::$params[$k]) && !self::validateDate(self::$params[$k]))
                                {
                                    self::$error = "Incorrect Date " .
                                        $k . " - " .
                                        self::$params[$k] . " - " .
                                        Config::$dateFormat;
                                }
                                break;
                            case "StartDate":
                                if (isset(self::$params[$k]) && strtotime(self::$params[$k]) > \time())
                                {
                                    self::$error = "Incorrect Start Date " .
                                        $k . " - " .
                                        self::$params[$k] . " - Today is " .
                                        date(Config::$dateFormat, \time());
                                }
                                break;
                            case "Key":
                                if (isset(self::$params[$k]) && self::$params[$k] !== Config::getRestKey())
                                {
                                    self::$error = "Incorrect REST API Key " . self::$params[$k];
                                }
                                break;
                            case "RuleCheck":
                                if (isset(self::$params[$k]) && Config::getDiscountRules(self::$params[$k]) === null)
                                {
                                    self::$error = "Incorrect Rule Type " . self::$params[$k];
                                }
                                break;
                            case "Int":
                                if (isset(self::$params[$k]) && !is_numeric(self::$params[$k]))
                                {
                                    self::$error = "Incorrect Value " . self::$params[$k];
                                }
                                break;
                            case "allow_export":
                                if (Config::getAllowExport() === 0) {
                                    self::$error = "Export not Allow";
                                }
                                break;
                            default:
                        }
                    }
                }
            }
        }

        return self::init();
    }

    public static function status() {
        return self::$error == null;
    }

    public static function error() {
        return self::$error;
    }

    /**
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public static function Output($data, $data1 = null, $name = null) {
        $mi = self::getParam('mime-type', Config::defMime);

        header("Content-type: " . self::$mime[$mi] . "; charset=utf-8");

        self::$getOut = "";

        switch ($mi) {
            case "xml":
                if (!is_array($data) && $data1 == null) {
                    self::$getOut = $data;
                } else {
                    if ($data1 == null) {
                        foreach ($data as $key=>$val) {
                            $data = $key;
                            $data1 = $val;
                        }
                    }

                    self::$getOut = Array2XML::cXML($data, $data1)->saveXML();
                }
                break;
            case 'json':
                if ($data1 !== null) {
                    $data = array($data => $data1);
                }
                self::$getOut = self::toJson($data);
                break;
            default:
                self::$getOut = $data;
        }

        return self::$getOut;
    }

    /** @noinspection PhpUnused */
    public static function getOutPut() {
        return self::$getOut;
    }

    /** @noinspection PhpComposerExtensionStubsInspection */
    public static function toJson($data = null){
        return json_encode(($data === null ? array() : $data),JSON_UNESCAPED_SLASHES);
    }
}
