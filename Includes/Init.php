<?php

ini_set('display_errors', true);
ini_set('memory_limit', 64000000); // imaging functions may require a lot of memory
error_reporting(E_ALL | E_STRICT);

ini_set('date.timezone', 'UTC');

include_once('../Includes/Logger.php');

$logger = new Logger(basename($_SERVER['PHP_SELF'], '.php'));
$logger->set_debug_level();

// FIXME - remove that because it logs passwords
if (count($_GET)  > 0) $logger->info('GET params : ' . print_r($_GET, true));
if (count($_POST) > 0) $logger->info('POST params: ' . print_r($_POST, true));

header('Content-type: text/html; charset=UTF-8');

?>