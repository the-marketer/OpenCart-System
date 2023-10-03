<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

/**
 * @property int|mixed|null $reviewStore
 */

class DataStorage
{
    private $data;
    private $name;

    public function __construct($name = null)
    {
        $this->name = $name === null ? 'def' : $name;
        FileSystem::setWorkDirectory('Storage');
        $data = FileSystem::rFile($this->name . ".json");

        if ($data !== '') {
            $this->data = json_decode($data, true);
        } else {
            $this->data = array();
        }
    }

    public function __get($name)
    {
        if (!isset($this->data[$name]))
        {
            if ($name == 'update_feed' || $name == 'update_review') {
                $this->data[$name] = 0;
            } else {
                $this->data[$name] = null;
            }
        }

        return $this->data[$name];
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function getData()
    {
        return $this->data;
    }

    public function addTo($name, $value, $key = null)
    {
        if ($key === null)
        {
            $this->data[$name][] = $value;
        } else {
            $this->data[$name][$key] = $value;
        }
    }

    public function del($name)
    {
        unset($this->data[$name]);
    }

    public function save()
    {
        FileSystem::setWorkDirectory('Storage');
        FileSystem::writeFile($this->name . ".json", Valid::toJson($this->data));
    }
}
