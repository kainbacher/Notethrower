<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Logger.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/PayPalTx.php');
include_once('../Includes/DB/ProjectFile.php');

// TODO - in case of error: show friendly error page with instructions for help

$project = Project::fetch_for_id(get_param('project_id'));
if (!$project || !$project->id) {
    $logger->warn('project not found!');
    echo 'PROJECT NOT FOUND!';
    exit;
}

$pFile = ProjectFile::fetch_for_id(get_numeric_param('atfid'));
if (!$pFile || !$pFile->id || $pFile->project_id != $project->id) {
    $logger->warn('project file not found!');
    echo 'FILE NOT FOUND!';
    exit;
}

if (get_param('mode') == 'purchase') {
    //check_authorization(); // deactivated, because it does not make sense to check. the content is free for private use anyway.
}

increment_download_count();

deliver_file($pFile);

exit;

function check_authorization() {
    global $logger;
    
    $logger->info('checking authorization ...');

    // check that we have a transaction and that the consumer's ip address is still the same
    $paypal_tx = PayPalTx::fetch_for_paypal_tx_id(get_param('transactionId'));
    if (!$paypal_tx || !$paypal_tx->id) {
        $logger->warn('no transaction record found for id: ' . get_param('transactionId'));
        echo 'INVALID REQUEST (1)';
        exit;
    }

    $logger->info('consumer ip from transaction: ' . $paypal_tx->payer_ip);
    $logger->info('client ip: ' . $_SERVER['REMOTE_ADDR']);

    if ($paypal_tx->payer_ip != $_SERVER['REMOTE_ADDR']) {
        $logger->warn('client ip/consumer ip mismatch!');
        echo 'INVALID REQUEST (2)';
        exit;
    }
}

function increment_download_count() {
    global $logger;
    global $project;

    $logger->info('incrementing project download count to ' . ($project->download_count + 1));

    $project->download_count++;
    $project->save();
}

function deliver_file(&$pFile) {
    global $logger;
    
    $logger->info('delivering content ...');

    $filename = $pFile->filename;
    if (!$filename) { // should never happen
        $logger->warn('filename not defined in ProjectFile object!');
        echo 'FILENAME MISSING!';
        exit;
    }

    $filepath = $GLOBALS['CONTENT_BASE_PATH'] . $filename;
    if (!file_exists($filepath)) {
        $logger->warn('file not found: ' . $filepath);
        echo 'FILE NOT FOUND! (' . $filename . ')'; // do not show filepath here, the filename is enough
        exit;
    }

    header('Content-Disposition: attachment; filename="' . $pFile->orig_filename . '"');
    header('Content-Type: ' . get_mime_type($pFile->orig_filename));
    header('Content-Length: ' . filesize($filepath));

    readfile_chunked($filepath);
}

function get_mime_type($filename) {
    $filename = strtolower($filename);

    if (strpos($filename, '.mp3') !== false) {
        return 'audio/mp3';
    } else if (strpos($filename, '.aiff') !== false) {
        return 'audio/x-aiff';
    } else if (strpos($filename, '.flac') !== false) {
        return 'audio/flac';
    } else if (strpos($filename, '.ogg') !== false) {
        return 'audio/ogg';
    } else if (strpos($filename, '.wav') !== false) {
        return 'audio/x-wav';
    } else if (strpos($filename, '.zip') !== false) {
        return 'application/zip';
    } else {
        return 'application/octet-stream'; // using this mimetype always ensures that no browser players are opened? FIXME
    }
}

?>


