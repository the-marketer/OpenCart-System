<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

class Logo
{
    private $data = array();

    public static $defData = null;

    public static function setDef() {
        if (self::$defData === null) {
            self::$defData = array(
                'style' => 'height:15px;margin-left:0px;margin-right:5px;vertical-align: middle;',
                'style2' => 'height:15px;margin-left:-30px;margin-right:7.5px;vertical-align: middle;',
                'style3' => 'height:20px;padding:0px;vertical-align: middle;',
                'style4' => 'height:35px;margin-left:0px;margin-right:5px;vertical-align: middle;',
                'logoLink' => 'data:image/png;base64,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',
                'Company' => 'The Marketer',
                'Module' => 'Tracker',
                'Format' => '<img style="{style}" src="{logoLink}" alt="{Company}"> {Company} - {Module}',
                'Format2' => '<img style="{style2}" src="{logoLink}" alt="{Company}"> {Company} - {Module}',
                'Format3' => '<img style="{style3}" src="{logoLink}" alt="{Company}"> {Text}',
                'Format4' => '<img style="{style3}" src="{logoLink}" alt="{Company}"> {Company} - {Module}',
                'formatTitle' => '{Company} - {Module}',
                'Text' => ''
            );
            if (Core::getOcVersion() >= "4") {
                self::$defData['style2'] = 'height:15px;margin-left:-5px;margin-right:7.5px;vertical-align: middle;';
            } else if (Core::getOcVersion() <= "2.3") {
                self::$defData['style2'] = 'height:15px;margin-left:0px;margin-right:7.5px;vertical-align: middle;';
                self::$defData['Format2'] = '{Company} - {Module}';
            }
        }
    }
    public function get($key) {
        return (isset($this->data[$key]) ? $this->data[$key] : null);
    }

    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function has($key) {
        return isset($this->data[$key]);
    }

    private static function getStrip($value) {
        return self::$defData[$value[1]];
    }

    private static function getRep($format) {
        return preg_replace_callback('/{(.+?)}/i', 'self::getStrip', $format);
    }
    public static function getMenuTitle($Module = null) {
        self::setDef();
        if ($Module !== null) { self::$defData['Module'] = $Module; }

        return self::getRep(self::$defData['Format']);
    }
    public static function getMenuTitle2($Module = null) {
        self::setDef();
        if ($Module !== null) { self::$defData['Module'] = $Module; }

        return self::getRep(self::$defData['Format2']);
    }

    public static function getH1($Module = null) {
        self::setDef();
        if ($Module !== null) { self::$defData['Module'] = $Module; }

        return self::getRep(self::$defData['Format4']);
    }
    public static function getText($Module = null) {
        self::setDef();
        if ($Module !== null) { self::$defData['Text'] = $Module; }

        return self::getRep(self::$defData['Format3']);
    }

    public static function getTitle($Module = null) {
        self::setDef();
        if ($Module !== null) { self::$defData['Module'] = $Module; }

        return self::getRep(self::$defData['formatTitle']);
    }
}