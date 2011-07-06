<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Logger.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/AudioTrackFile.php');
include_once('../Includes/DB/PayPalTx.php');

// TODO - in case of error: show friendly error page with instructions for help

$track = AudioTrack::fetch_for_id(get_param('track_id'));
if (!$track || !$track->id) {
    $logger->warn('track not found!');
    echo 'TRACK NOT FOUND!';
    exit;
}

$atFile = AudioTrackFile::fetch_for_id(get_numeric_param('atfid'));
if (!$atFile || !$atFile->id || $atFile->track_id != $track->id) {
    $logger->warn('track not found!');
    echo 'FILE NOT FOUND!';
    exit;
}

if (get_param('mode') == 'purchase') {
    //check_authorization(); // deactivated, because it does not make sense to check. the content is free for private use anyway.
}

increment_download_count();

deliver_file($atFile);

exit;

function check_authorization() {
    global $logger;
    global $track;

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
    global $track;

    $logger->info('incrementing track download count to ' . ($track->download_count + 1));

    $track->download_count++;
    $track->save();
}

function deliver_file(&$atFile) {
    global $logger;
    global $track;

    $logger->info('delivering content ...');

    $filename = $atFile->filename;
    if (!$filename) { // should never happen
        $logger->warn('filename not defined in AudioTrackFile object!');
        echo 'FILENAME MISSING!';
        exit;
    }

    $filepath = $GLOBALS['CONTENT_BASE_PATH'] . $filename;
    if (!file_exists($filepath)) {
        $logger->warn('file not found: ' . $filepath);
        echo 'FILE NOT FOUND! (' . $filename . ')'; // do not show filepath here, the filename is enough
        exit;
    }

    header('Content-Disposition: attachment; filename="' . $atFile->orig_filename . '"');
    header('Content-Type: ' . get_mime_type($atFile->type));
    header('Content-Length: ' . filesize($filepath));

    readfile_chunked($filepath);
}

function get_mime_type($fmt) {
    if ($fmt == 'HQMP3') {
        return 'audio/mp3';
    } else if ($fmt == 'AIFF') {
        return 'audio/x-aiff';
    } else if ($fmt == 'FLAC') {
        return 'audio/flac';
    } else if ($fmt == 'OGG') {
        return 'audio/ogg';
    } else if ($fmt == 'WAV') {
        return 'audio/x-wav';
    } else if ($fmt == 'ZIP') {
        return 'application/zip';
    } else {
        return 'application/octet-stream'; // using this mimetype always ensures that no browser players are opened? FIXME
    }
}

?>


