<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

use Mktr\Helper\Model\Module;
use Mktr\Tracker\Observer;

class Form
{
    private static $form_fields = array();
    private static $init = null;
    private static $notice = array();

    private static $defFields = array(
        'title' => '',
        'type' => 'text',
        'default' => '',
        'description' => '',
        'holder' => '',
        'options' => array(
            array('value' => 0, 'label' => "Disable"),
            array('value' => 1, 'label' => "Enable")
        )
    );

    public static function init() {
        if (self::$init == null) {
            self::$init = new self();
        }

        return self::$init;
    }

    public static function formFields($fields, $id = 0)
    {
        foreach ($fields as $key=>$value)
        {
            $fields[$key] = array_merge(self::$defFields, $value);
        }

        if (!isset(self::$form_fields[$id])) { self::$form_fields[$id] = array(); }

        self::$form_fields[$id] = array_merge(self::$form_fields[$id], $fields);

        return self::init();
    }

    public static function initProcess()
    {
        if(isset($_POST[Core::getModuleCode()]))
        {
            $fail = false;
            $conf = Config::init();
            foreach ($_POST[Core::getModuleCode()] as $key=>$value)
            {
                if (in_array($key, array('tracking_key', 'rest_key', 'customer_id', 'google_tagCode')) && empty($value)) {
                    $fail = $key;
                }

                if ($key === 'status') {
                    if (Config::$addToModule) {
                        $module_id = $conf->get('module_id');

                        $data = Module::getModule($module_id);
                        $data['status'] = $value;

                        Module::editModule($module_id, $data);
                    }
                    $conf->set($key, $value);
                    // mkConfig::saveSetting($key, $value, Core::getStoreID());
                } else {
                    $conf->set($key, $value);
                    // mkConfig::saveSetting($key, $value, Core::getStoreID());
                }

                if ($key == 'push_status') {
                    Observer::pushStatus();
                }
            }

            $conf->save();

            if ($fail) {
                self::$notice[] = array(
                    'type' => 'danger',
                    'message'=> 'Please fill are Require(*) fields'
                );

            } else {
                self::$notice[] =
                    array(
                        'message'=>'Your settings have been saved.'
                    );
            }
        }
    }
    public static function getNotice() {
        return self::$notice;
    }

    public static function getForm($clean = false)
    {
        $out = array();
        $send = array();
        $conf = Config::init();

        $form = Core::getOcVersion() >= "4" ? ' class="row mb-3"' : ' class="form-group"';

        foreach (self::$form_fields as $key0=>$value0)
        {
            foreach ($value0 as $key => $value) {

                $out[] = '<div'.$form.'>
    <label class="col-sm-2 control-label" ' . ($value['type'] != 'title' ? ' for="'.Core::getModuleCode().'_'.$key.'"' : '' ) . '>'.$value['title'].'</label>
    <div class="col-sm-10">';

                if ($value['type'] !== 'empty' && $value['type'] !== 'title') {
                    $value['default'] = ($value['default'] !== '' ? $value['default'] : $conf->get($key));
                }

                /** @noinspection PhpSwitchStatementWitSingleBranchInspection */
                switch ($value['type'])
                {
                    case 'empty':
                    case 'title':

                        break;
                    case 'select':

                        $out[] = '<select class="form-control"
                        name="'.Core::getModuleCode().'['.$key.']" id="'.Core::getModuleCode().'_'.$key.'">';
                        foreach ($value['options'] as $o)
                        {
                            $out[] = '<option value="'.$o['value'].'" '.( $value['default'] == $o['value'] ?
                                    'selected="selected" ' : '').'>'.$o['label'].'</option>';
                        }
                        $out[] = '</select>';
                        break;
                    default:
                        if (is_array($value['default']))
                        {
                            $value['default'] = implode('|', $value['default']);
                        }
                        $out[] = '                    <input class="form-control"
                        type="text"
                        name="'.Core::getModuleCode().'['.$key.']"
                        id="'.Core::getModuleCode().'_'.$key.'"
                        value="'.str_replace('"',"'",$value['default']).'" '.(
                            $value['holder'] !== '' ?
                                'placeholder="'.$value['holder'].'" ' : ''
                            ).'/>';
                }


                if ($value['description'] !== '' )
                {
                    $out[] = '                    <p class="description">'.$value['description'].'</p>';
                }

                $out[] = '</div></div>';
            }
            $send[$key0] = implode(PHP_EOL, $out);
            $out = array();
        }

        if ($clean)
        {
            self::clean();
        }

        return $send;
    }

    public static function clean()
    {
        self::$form_fields = array();
    }

}