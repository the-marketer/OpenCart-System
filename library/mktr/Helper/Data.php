<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

/**
 * @property int|mixed|null $reviewStore
 */

class Data
{
    private static $init = null;

    private static $data;

    public function __construct()
    {
        FileSystem::setWorkDirectory();
        $data = FileSystem::rFile("data.json");
        if ($data !== '')
        {
            self::$data = json_decode($data, true);
        } else {
            self::$data = array();
        }
    }
    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public function __get($name)
    {
        if (!isset(self::$data[$name]))
        {
            if ($name == 'update_feed' || $name == 'update_review') {
                self::$data[$name] = 0;
            } else {
                self::$data[$name] = null;
            }
        }

        return self::$data[$name];
    }

    public function __set($name, $value)
    {
        self::$data[$name] = $value;
    }

    public static function getData()
    {
        return self::$data;
    }

    public static function addTo($name, $value, $key = null)
    {
        if ($key === null)
        {
            self::$data[$name][] = $value;
        } else {
            self::$data[$name][$key] = $value;
        }
    }

    public static function del($name)
    {
        unset(self::$data[$name]);
    }

    public static function save()
    {
        FileSystem::writeFile("data.json", Valid::toJson(self::$data));
    }
}
