<?php
/** @noinspection SpellCheckingInspection */
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

class FileSystem
{
    private static $path = null;
    private static $lastPath = null;
    private static $status = array();

    private static $init = null;

    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }
        return self::$init;
    }

    /** @noinspection PhpUnused */
    public static function setWorkDirectory($name = 'Storage')
    {
        if ($name == 'base')
        {
            self::$path = MKTR_ROOT;
        } else {
            self::$path = MKTR_LIB . $name . "/";
        }

        return self::init();
    }

    /** @noinspection PhpUnused */
    public static function writeFile($fName, $content, $mode = 'w+')
    {
        self::$lastPath = self::getPath() . $fName;

        $file = fopen(self::$lastPath, $mode);
        fwrite($file, $content);
        fclose($file);

        self::$status[] = array(
            'path' => self::getPath(),
            'fileName' => $fName,
            'fullPath' => self::getPath() . $fName,
            'status' => true
        );

        return self::init();
    }

    /** @noinspection PhpUnused */
    public static function rFile($fName, $mode = "rb")
    {
        self::$lastPath = self::getPath() . $fName;

        if(self::fileExists($fName))
        {
            $file = fopen(self::$lastPath, $mode);
            $size = filesize(self::$lastPath);
            if ($size > 0) {
                $contents = fread($file, $size);
            } else {
                $contents = '';
            }

            fclose($file);
        } else {
            $contents = '';
        }

        return $contents;
    }

    /** @noinspection PhpUnused */
    public static function readFile($fName, $mode = "rb")
    {
        $contents = '';
        self::$lastPath = self::getPath() . $fName;

        if(self::fileExists($fName))
        {
            $file = fopen(self::$lastPath, $mode);

            $contents = fread($file, filesize(self::$lastPath));

            fclose($file);
        }

        return $contents;
    }

    /** @noinspection PhpUnused */
    public static function fileExists($fName)
    {
        return file_exists(self::getPath() . $fName);
    }

    /** @noinspection PhpUnused */
    public static function deleteFile($fName)
    {
        self::$lastPath = self::getPath() . $fName;

        if(self::fileExists($fName))
        {
            unlink(self::$lastPath);
        }
        return true;
    }

    public static function getPath()
    {
        if (self::$path == null)
        {
            self::setWorkDirectory();
        }
        return self::$path;
    }

    /** @noinspection PhpUnused */
    public static function getLastPath()
    {
        return self::$lastPath;
    }

    public static function getStatus()
    {
        return self::$status;
    }
}
