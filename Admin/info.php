<?php

error_reporting (E_ALL ^ E_NOTICE);

if (ini_get('safe_mode')) {
    echo 'safe mode is on';
} else {
    phpinfo();
}

?>
