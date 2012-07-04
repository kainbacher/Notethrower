<?php

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');

if ($db = mysql_connect($GLOBALS['DATABASE_HOST'], $GLOBALS['DATABASE_USERNAME'], $GLOBALS['DATABASE_PASSWORD'])) {
    mysql_select_db($GLOBALS['DATABASE_NAME'], $db);

} else {
    show_fatal_error_and_exit(
        'Cannot establish database connection! ' . mysql_error()
    );
}

function _mysql_query($sql) {
    global $logger;

    $logger->debug($sql);

    $resp = mysql_query($sql);

    if (!$resp) {
        show_fatal_error_and_exit(
            'SQL operation failed: ' . mysql_error() . ' [STMT: ' . $sql . ']'
        );
    }

    return $resp;
}

?>
