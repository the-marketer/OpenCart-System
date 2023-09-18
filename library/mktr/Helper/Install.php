<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

class Install
{
    private static $paths = array(
        /*'2.3' => array(
            'controller' => 'controller/extension/module/',
            'language' => 'language/en-gb/extension/module/'
        ),
        '1.5' => array(
            'controller' => 'controller/module/',
            'language' => 'language/english/module/'
        )*/
        '2.3' => array(
            'controller' => 'extension/module',
            'language' => 'en-gb'
        ),
        '1.5' => array(
            'controller' => 'module',
            'language' => 'english'
        )
    );

    private static $data = array();

    public static function getPaths() {
        if (!isset(self::$data['path'])) {
            foreach (self::$paths as $key => $val) {
                if (!defined('VERSION') || $key <= VERSION) {
                    self::$data['path'] = $val;
                    break;
                }
            }
        }
        return self::$data['path'];
    }
}
