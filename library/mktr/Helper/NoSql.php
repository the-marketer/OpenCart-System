<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

abstract class NoSql
{
    protected $file = "logs.json";
    protected $dir = "Storage";
    protected $isDirty = false;
    protected $path = null;
    protected $size = null;
    protected $exists = null;
    protected $fOpen = null;
    protected $content = null;
    protected $data = [];
    protected $original = [];

    public function __construct() {
        $this->refresh();
    }

    public function __get($key) {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return null;
        }
    }

    public function __set($key, $value) {
        $this->data[$key] = $value;
        $this->isDirty = true;
    }

    public function save() {
        if ($this->isDirty) {
            $this->isDirty = false;
            $this->fOpen = fopen($this->path, 'w+');
            fwrite($this->fOpen, json_encode($this->data, JSON_UNESCAPED_SLASHES));
            fclose($this->fOpen);
            $this->original = $this->data;
        }
        return $this;
    }

    public function refresh() {
        $this->size = null;
        $this->exists = null;
        $this->path = MKTR_LIB . $this->dir . "/" . $this->file;

        if ($this->fileExists() && $this->fileSize()) {
            $this->fOpen = fopen($this->path, "rb");
            $this->content = fread($this->fOpen, (int) $this->size);
            fclose($this->fOpen);
        }

        if ($this->content !== null) {
            $this->original = json_decode($this->content, true);
            $this->data = $this->original;
        }
        return $this;
    }

    public function getData() {
        return $this->data;
    }

    public function addTo($name, $value, $key = null) {
        if ($key === null) {
            $this->data[$name][] = $value;
        } else {
            $this->data[$name][$key] = $value;
        }
        $this->isDirty = true;
        return $this;
    }

    public function addToIfNot($name, $value) {
        if (!isset($this->data[$name])) {
            $this->data[$name] = array();
        }

        if (!in_array($value, $this->data[$name])) {
            $this->data[$name][] = $value;
            $this->isDirty = true;
        }
        return $this;
    }

    public function del($name) {
        unset($this->data[$name]);
        $this->isDirty = true;
        return $this;
    }

    protected function fileExists() {
        if ($this->exists === null) { $this->exists = file_exists($this->path); }
        return $this->exists;
    }

    protected function fileSize() {
        if ($this->size === null) { $this->size = filesize($this->path); }
        return $this->size > 0;
    }
}
