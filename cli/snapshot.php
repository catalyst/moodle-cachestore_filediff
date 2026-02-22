<?php
define('CLI_SCRIPT', true);
define('IGNORE_COMPONENT_CACHE', true);

require(__DIR__.'/../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

$cfg = get_config('cachestore_deltagibbon');
if (empty($cfg->snapshot)) {
    $snapshot = 1;
    set_config('snapshot', $snapshot, 'cachestore_deltagibbon');
} else {
    $snapshot = $cfg->snapshot + 1;
    set_config('snapshot', $snapshot, 'cachestore_deltagibbon');
}

cli_heading("Now using snap shot $snapshot");

