<?php

use core_cache\store;
use core_cache\definition;

class cachestore_filediff extends store implements cache_is_key_aware {

    /** @var string */
    protected $path;

    protected $definition;

    public function __construct($name, array $configuration = []) {
        parent::__construct($name, $configuration);


    }
    public function my_name() {
        return 'Delta gibbon';
    }

    public function initialise(definition $definition) {
        global $CFG;
        $this->definition = $definition;
        $defid = preg_replace('#[^a-zA-Z0-9]+#', '_', $this->definition->get_id());

        $cfg = get_config('cachestore_filediff');
        if (empty($cfg->snapshot)) {
            $snapshot = 1;
            set_config('snapshot', $snapshot, 'cachestore_filediff');
        } else {
            $snapshot = $cfg->snapshot;
        }

        $this->path = "$CFG->dataroot/cache-filediff/$defid/$snapshot";
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
        if (!is_dir($this->path)) {
            throw new moodle_exception('cannot create filedir dir');
        }
    }

    public function is_initialised() {
        return true;
    }

    public static function are_requirements_met() {
        return true;
    }

    public static function initialise_instance($name, $configuration) {
        return new self($name, $configuration);
    }

    public static function get_supported_features(array $configuration = []) {

        // HAHA! I Lie!
        return self::SUPPORTS_DATA_GUARANTEE;
    }

    protected function keypath($key) {
        return $this->path . '/' . $key . '.json';
    }

    protected function readfile($file) {
        if (!file_exists($file)) {
            return null;
        }

        $contents = file_get_contents($file);
        if ($contents === false) {
            return null;
        }

        return json_decode($contents, true);
    }

    protected function writefile($file, array $data) {
        file_put_contents(
            $file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }

    public function get($key) {

        $file = $this->keypath($key);
        $data = $this->readfile($file);

        if (!$data || empty($data['value'])) {
            return false;
        }

        // TODO we will simulate cache misses and we'll store the current
        // version as a new file so its easy to diff, and then wait for
        // the newly rebuild version to come through. Then we'll check if
        // they are exactly binary compatible and if they are not then we
        // have found a cache corruption and we are not invalidating enough!

        // When this happens weneed to simulate clearing everything! Not just
        // one key.


        return unserialize($data['value']);
    }

    function ksort(&$array) {
        if (!is_array($array)) {
            return;
        }

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksort($value);
            }
        }

        if (array_keys($array) !== range(0, count($array) - 1)) {
            ksort($array); // sort associative keys only
        }
    }

    public function set($key, $value) {

        $file = $this->keypath($key);
        $data = $this->readfile($file);

        $json = json_decode(json_encode($value), true);
        $this->ksort($json);

        // TODO this is where we would check if the value currently
        // stored is actually the same as this. Which suggests we are
        // rebuilding more than we should be.

        $backtrace = debug_backtrace();

        if (!$data) {
            $data = [
                "metadata" => [
                    'key'       => $key,
                    'backtrace' => explode("\n", trim(format_backtrace($backtrace, true))),
                ],
                'json'      => $json,
                'value'     => serialize($value),
            ];
        }

        $this->writefile($file, $data);

        return true;
    }

    public function delete($key) {

        debugging("Never delete! Never surrender! Key = $key");
        // throw new moodle_exception('Never delete! Never surrender!');

    }
    public function delete_many(array $keys) {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function purge() {
        // Ha! Thats what you think...
        // We'll keep the old files to compare.

        $cfg = get_config('cachestore_filediff');
        if (empty($cfg->snapshot)) {
            $snapshot = 1;
            set_config('snapshot', $snapshot, 'cachestore_filediff');
        } else {
            $snapshot = $cfg->snapshot + 1;
            set_config('snapshot', $snapshot, 'cachestore_filediff');
        }

        return true;
    }

    public function has($key) {
        $file = $this->keypath($key);
        $data = $this->readfile($file);
        return !empty($data['current']);
    }
    public function has_any(array $keys) {
        foreach ($keys as $key) {
            if ($this->has($key)) {
                return true;
            }
        }
        return false;
    }
    public function has_all(array $keys) {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }
        return true;
    }

    public function get_many($keys) {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function set_many(array $keyvaluearray) {
        foreach ($keyvaluearray as $key => $value) {
            $this->set($key, $value);
        }
        return true;
    }
    public static function is_supported_mode($mode) {
        return ($mode === static::MODE_APPLICATION);
    }
    public static function get_supported_modes(array $configuration = array()) {
        return self::MODE_APPLICATION ;
    }


    public static function initialise_test_instance(definition $definition) {
    }

    public static function unit_test_configuration() {
        return array();
    }
}

