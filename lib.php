<?php

use core_cache\store;
use core_cache\definition;

class cachestore_deltagibbon extends store implements cache_is_key_aware {

    /** @var string */
    protected $path;

    /** @var int */
    protected $missinterval;

    /** @var array */
    protected $readcounts = [];

    protected $definition;

    public function __construct($name, array $configuration = []) {
        parent::__construct($name, $configuration);

        global $CFG;

        $this->missinterval = 3;

        if (!empty($configuration['missinterval'])) {
            $this->missinterval = (int)$configuration['missinterval'];
        }

    }
    public function my_name() {
        return 'Delta gibbon';
    }

    public function initialise(definition $definition) {
        global $CFG;
        $this->definition = $definition;
        $hash = preg_replace('#[^a-zA-Z0-9]+#', '_', $this->definition->get_id());

        $this->path = $CFG->dataroot . '/deltagibbon/' . $hash;
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
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
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    public function get($key) {

        $file = $this->keypath($key);
        $data = $this->readfile($file);

        if (!$data || empty($data['value'])) {
            return false;
        }

        // Simulate forced miss every N reads.
        // $this->readcounts[$key] = ($this->readcounts[$key] ?? 0) + 1;
        //
        // if ($this->missinterval > 0 &&
        //     $this->readcounts[$key] % $this->missinterval === 0) {
        //
        //     debugging("deltagibbon: Forced miss for key {$key}", DEBUG_DEVELOPER);
        //     return false;
        // }

// echo '<pre>';
// var_dump($data['value']);
// die;

        return unserialize($data['value']);
    }


    public function set($key, $value) {

        $file = $this->keypath($key);
        $data = $this->readfile($file);

        if (!$data) {
            $data = [
                "metadata" => [
                    'key'       => $key,
                    'created'   => time(),
                ],
                'json'  => $value,
                'value' => serialize($value),
            ];
        }

        $this->writefile($file, $data);

        return true;
    }

    public function delete($key) {

        debugging('Never delete! Never surrender!');
        // throw new moodle_exception('Never delete! Never surrender!');

    }
    public function delete_many(array $keys) {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function purge() {

        foreach (glob($this->path . '/*.json') as $file) {
            unlink($file);
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

