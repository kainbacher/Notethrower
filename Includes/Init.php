<?php

ini_set('display_errors', true);
ini_set('memory_limit', 64000000); // imaging functions may require a lot of memory
error_reporting(E_ALL | E_STRICT);

ini_set('date.timezone', 'UTC');

include_once('../Includes/Logger.php');

$logger = new Logger(basename($_SERVER['PHP_SELF'], '.php'));
$logger->set_info_level();

if (count($_GET) > 0) {
    $logger->info('GET params:');
    foreach($_GET as $key => $val) {
        if ($key != 'password' && $key != 'pwd' && $key != 'pass') $logger->info('  ' . $key . ' = ' . $val);
    }
}
if (count($_POST) > 0) {
    $logger->info('POST params:');
    foreach($_POST as $key => $val) {
        if ($key != 'password' && $key != 'pwd' && $key != 'pass') $logger->info('  ' . $key . ' = ' . $val);
    }
}

header('Content-type: text/html; charset=UTF-8');

?>