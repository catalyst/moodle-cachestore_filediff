<?php
define('CLI_SCRIPT', true);
define('IGNORE_COMPONENT_CACHE', true);

require(__DIR__.'/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

cli_heading("Reset all snapshots");
fulldelete("$CFG->dataroot/cache-filediff/");

unset_config('snapshot', 'cachestore_filediff');
