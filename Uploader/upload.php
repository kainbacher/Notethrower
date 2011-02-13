<?php

include_once('../Includes/Init.php');
include_once('../Includes/Snippets.php');
include_once('../Uploader/SolmetraUploader.php');

@set_time_limit(0); // no timeout for this script

ob_start();
$solmetraUploader = new SolmetraUploader();
$solmetraUploader->handleFlashUpload();
$output = ob_get_contents();
ob_end_clean();

$logger->info('response: ' . $output);

echo $output;

?>