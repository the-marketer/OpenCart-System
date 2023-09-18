<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

abstract class DB
{
    public function install()
    {
        if (isset($this->columns)) {
            $query = array(
                "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . $this->table . "` ("
            );

            foreach ($this->columns as $key => $value) {
                $query[] =  "`" . $key . "` " . $value;
            }

            if ($this->primary !== null) {
                $query[] =  "PRIMARY KEY (`" . $this->primary . "`)";
            }

            $query[] = ") ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;";

            Core::query(implode(PHP_EOL, $query));
        }
    }

    public function uninstall()
    {
        // drop table
        Core::query("DROP TABLE IF EXISTS `" . DB_PREFIX . $this->table . "`");
    }

    public function fixAuto() {
        $query = $this->selectAll();

        Core::query("TRUNCATE TABLE `" . DB_PREFIX . $this->table . "`");

        foreach ($query->rows as $v) {
            $q = array();
            foreach ($v as $key => $value) {
                if ($key !== $this->auto) {
                    $q[] = "`" . $key . "` = '" . Core::escape($value) . "'";
                }
            }

            $q = "INSERT INTO `" . DB_PREFIX . $this->table . "` SET " . implode(', ', $q);
            Core::query($q);
        }
    }

    public function selectAll($data = null) {
        $q = "SELECT * FROM `" . DB_PREFIX . $this->table . "`";

        if ($data !== null) {
            foreach ($data as $key => $value) {
                if ($key === 'order') {
                    $q .= " ORDER BY " . $value;
                }
            }
        }

        return Core::query($q);
    }
}
