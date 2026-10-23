<?php
/**
 * @copyright   © EAX LEX SRL. All rights reserved.
 **/

namespace Mktr\Helper;

/**
 * Registrul comenzilor trimise la theMarketer: order_id => order_status_id trimis.
 *
 * Fișierul e scris și din browser (saveOrder), și din cron, așa că la salvare
 * recitim ce e pe disc și aplicăm peste doar modificările noastre - altfel cine
 * salvează ultimul șterge ce a scris celălalt între timp.
 */
class OrderLogs extends NoSql
{
    /* comanda a ajuns la theMarketer, dar nu știm cu ce status */
    const UNKNOWN = -1;
    const MAX = 10000;
    const MAX_TRIES = 3;

    private static $init = null;
    protected $file = "orders.json";

    private $changes = array();
    private $meta = array();
    private $retries = array();

    public static function i() {
        if (self::$init === null) {
            self::$init = new self();
        }
        return self::$init;
    }

    public function refresh() {
        $this->content = null;
        $this->data = array();

        parent::refresh();

        if (!is_array($this->data)) {
            $this->data = array();
        }

        $this->migrate();

        return $this;
    }

    /**
     * Până la v1.2.1 registrul era doar o listă de ID-uri ('orderIDs'). Le păstrăm
     * ca trimise, cu status necunoscut, ca să nu le retrimitem pe toate.
     */
    private function migrate() {
        if (!isset($this->data['orders']) || !is_array($this->data['orders'])) {
            $this->data['orders'] = array();
        }

        if (!isset($this->data['retry']) || !is_array($this->data['retry'])) {
            $this->data['retry'] = array();
        }

        if (isset($this->data['orderIDs'])) {
            if (is_array($this->data['orderIDs'])) {
                foreach ($this->data['orderIDs'] as $id) {
                    if (!isset($this->data['orders'][(int) $id])) {
                        $this->data['orders'][(int) $id] = self::UNKNOWN;
                    }
                }
            }

            unset($this->data['orderIDs']);
            $this->isDirty = true;
        }
    }

    /**
     * null = comanda n-a ajuns niciodată la theMarketer. Comenzile mai vechi decât
     * ce ține registrul (au fost tăiate la MAX) le considerăm ajunse.
     */
    public function statusOf($id) {
        $id = (int) $id;

        if (isset($this->data['orders'][$id])) {
            return (int) $this->data['orders'][$id];
        }

        if (isset($this->data['floor']) && $id < (int) $this->data['floor']) {
            return self::UNKNOWN;
        }

        return null;
    }

    public function markSent($id, $statusId) {
        $id = (int) $id;

        $this->data['orders'][$id] = (int) $statusId;
        $this->changes[$id] = (int) $statusId;
        $this->clearRetry($id);
        $this->isDirty = true;

        return $this;
    }

    /* actualizează statusul doar dacă theMarketer știe deja de comandă */
    public function markStatus($id, $statusId) {
        if ($this->statusOf($id) !== null) {
            $this->markSent($id, $statusId);
        }
        return $this;
    }

    public function getRetries() {
        return $this->data['retry'];
    }

    /* întoarce false când comanda a epuizat încercările și trebuie lăsată în pace */
    public function addRetry($id) {
        $id = (int) $id;
        $tries = isset($this->data['retry'][$id]) ? (int) $this->data['retry'][$id] + 1 : 1;

        if ($tries > self::MAX_TRIES) {
            $this->clearRetry($id);
            return false;
        }

        $this->data['retry'][$id] = $tries;
        $this->retries[$id] = $tries;
        $this->isDirty = true;

        return true;
    }

    public function clearRetry($id) {
        $id = (int) $id;

        if (isset($this->data['retry'][$id])) {
            unset($this->data['retry'][$id]);
            $this->retries[$id] = 0;
            $this->isDirty = true;
        }
    }

    public function getMeta($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function setMeta($key, $value) {
        $this->data[$key] = $value;
        $this->meta[$key] = $value;
        $this->isDirty = true;

        return $this;
    }

    public function save() {
        if (!$this->isDirty) {
            return $this;
        }

        $changes = $this->changes;
        $meta = $this->meta;
        $retries = $this->retries;

        $this->refresh();

        foreach ($changes as $id => $statusId) {
            $this->data['orders'][$id] = $statusId;
        }

        foreach ($retries as $id => $tries) {
            if ($tries > 0) {
                $this->data['retry'][$id] = $tries;
            } else {
                unset($this->data['retry'][$id]);
            }
        }

        foreach ($meta as $key => $value) {
            $this->data[$key] = $value;
        }

        if (count($this->data['orders']) > self::MAX) {
            ksort($this->data['orders'], SORT_NUMERIC);
            $this->data['orders'] = array_slice($this->data['orders'], -self::MAX, null, true);

            reset($this->data['orders']);
            $this->data['floor'] = key($this->data['orders']);
        }

        $this->isDirty = true;
        parent::save();

        $this->changes = array();
        $this->meta = array();
        $this->retries = array();

        return $this;
    }
}
