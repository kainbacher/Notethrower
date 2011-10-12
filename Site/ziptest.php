<?php

include_once('../Includes/Snippets.php');

$zipfile = putProjectFilesIntoZip(array());
if ($zipfile === false) {
    echo 'OUCH!';
    exit;
}

echo $zipfile;

?>
