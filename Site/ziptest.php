<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');

$zipfile = putProjectFilesIntoZip(array(66,67));
if ($zipfile === false) {
    echo 'OUCH!';
    exit;
}

echo $zipfile;

?>
