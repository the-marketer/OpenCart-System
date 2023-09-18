<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

class Api
{
    private static $init = null;

    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    private static $mURL = "https://t.themarketer.com/api/v1/";
    // private static $mURL = "https://eaxdev.ga/mktr/EventsTrap/";
    private static $bURL = "https://eaxdev.ga/mktr/BugTrap/";

    private static $timeOut = null;

    private static $cURL = null;

    private static $params = null;
    private static $lastUrl = null;

    private static $info = null;
    private static $exec = null;
    private static $requestType = null;
    /** @noinspection PhpUnused */
    public static function send($name, $data = array(), $post = true)
    {
        return self::REST(self::$mURL . $name, $data, $post);
    }

    /** @noinspection PhpUnused */
    public static function debug($data = array(), $post = true)
    {
        return self::REST(self::$bURL, $data, $post);
    }

    /** @noinspection PhpUnused */
    public static function getParam()
    {
        return self::$params;
    }

    /** @noinspection PhpUnused */
    public static function getUrl()
    {
        return self::$lastUrl;
    }

    /** @noinspection PhpUnused */
    public static function getStatus()
    {
        return self::$info["http_code"];
    }

    /** @noinspection PhpUnused */
    public static function getInfo()
    {
        return self::$info;
    }

    /** @noinspection PhpUnused */
    public static function getContent()
    {
        return self::$exec;
    }

    public static function getBody()
    {
        return self::$exec;
    }

    public static function REST($url, $data = array(), $post = true)
    {
        try {
            if (Config::getRestKey() === null) {
                return false;
            }

            if (self::$timeOut == null)
            {
                self::$timeOut = 1;
            }

            self::$params = array_merge(array(
                'k' => Config::getRestKey(),
                'u' => Config::getCustomerId()
            ), $data);


            self::$requestType = $post;

            if (self::$requestType)
            {
                self::$lastUrl = $url;
            } else {
                self::$lastUrl = $url . '?' . http_build_query(self::$params);
            }

            self::$cURL = \curl_init();

            \curl_setopt(self::$cURL, CURLOPT_CONNECTTIMEOUT, self::$timeOut);
            \curl_setopt(self::$cURL, CURLOPT_TIMEOUT, self::$timeOut);
            \curl_setopt(self::$cURL, CURLOPT_URL, self::$lastUrl);
            \curl_setopt(self::$cURL, CURLOPT_POST, self::$requestType);

            if (self::$requestType) {
                \curl_setopt(self::$cURL, CURLOPT_POSTFIELDS, http_build_query(self::$params));
            }

            \curl_setopt(self::$cURL, CURLOPT_RETURNTRANSFER, true);
            \curl_setopt(self::$cURL, CURLOPT_SSL_VERIFYPEER, false);

            self::$exec = \curl_exec(self::$cURL);

            self::$info = \curl_getinfo(self::$cURL);

            \curl_close(self::$cURL);
        } catch (\Exception $e) {

        }
        return self::init();
    }

}
