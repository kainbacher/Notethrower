<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');

deleteOldFilesMatchingPatternInDirectory('*.zip', $GLOBALS['TEMP_FILES_BASE_PATH'], 3); // cleanup old temp zip files first

$zipfile = putProjectFilesIntoZip(array(66,67));
if ($zipfile === false) {
    echo 'OUCH!';
    exit;
}

echo $zipfile;

?>
