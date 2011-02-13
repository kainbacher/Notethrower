<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Artist.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/AudioTrackFile.php');
include_once('../Includes/DB/Stats.php');

// let's see if the visiting user is a logged in artist
$visitorArtistId = -1;
$visitorArtist = Artist::new_from_cookie();
if ($visitorArtist) {
    $visitorArtistId = $visitorArtist->id;
    $logger->info('visitor artist id: ' . $visitorArtistId);
}

$aid = get_numeric_param('aid');

if (!$aid) {
    show_fatal_error_and_exit('Artist ID missing!');
}

$artist = Artist::fetch_for_id($aid);

if (!$artist || !$artist->id) {
    show_fatal_error_and_exit('Artist not found!');
}

$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
$xml .= '<ppArtistData>';
$xml .= '<id>' . $artist->id . '</id>';
$xml .= '<name>' . xmlentities($artist->name) . '</name>';
$xml .= '<imageFile>' . xmlentities($artist->image_filename) . '</imageFile>';
//$xml .= '<webpageUrl>' . xmlentities($artist->webpage_url) . '</webpageUrl>';
$xml .= '<webpageUrl>' . xmlentities($GLOBALS['BASE_URL'] . 'Site/artistInfo.php?aid=' . $artist->id) . '</webpageUrl>';
$xml .= '<tracks>';

// load originals
$tracks = AudioTrack::fetch_all_originals_of_artist_id_from_to($aid, 0, 9999, false, false, $visitorArtistId); // FIXME - paging - use Paginator
foreach ($tracks as $track) {
    processTrack($xml, $track, false);
}

// load the remixes
$tracks = AudioTrack::fetch_all_remixes_of_artist_id_from_to($aid, 0, 9999, false, false, $visitorArtistId); // FIXME - paging - use Paginator
foreach ($tracks as $track) {
    processTrack($xml, $track, true);
}

// load the songs which were remixed by others
$tracks = AudioTrack::fetch_all_remixes_for_originating_artist_id_from_to($aid, 0, 9999, false, false, $visitorArtistId); // FIXME - paging - use Paginator
foreach ($tracks as $track) {
    processTrack($xml, $track, true);
}

$xml .= '</tracks>';
$xml .= '</ppArtistData>';

header('Content-type: text/xml; charset=UTF-8');
header('Content-length: ' . strlen($xml));
echo $xml;

// record the access
if ($artist->id && $_SERVER['REMOTE_ADDR']) {
    $stats = new Stats();
    $stats->artist_id = $artist->id;
    $stats->ip        = $_SERVER['REMOTE_ADDR'];
    $stats->insert();
}

function processTrack(&$xml, &$track, $remixedByOthersMode) {
    $xml .= '<track>';

    $xml .= '<id>' . $track->id . '</id>';
    $xml .= '<type>' . $track->type . '</type>';

    if ($track->type == 'remix') {
        $xml .= '<remixerArtistId>' . $track->artist_id . '</remixerArtistId>';
        $xml .= '<remixerArtistName>' . $track->artist_name . '</remixerArtistName>';
        $xml .= '<originatingArtistId>' . $track->originating_artist_id . '</originatingArtistId>';
        $xml .= '<originatingArtistName>' . $track->originating_artist_name . '</originatingArtistName>';

    } else {
        $xml .= '<remixerArtistId/>';
        $xml .= '<originatingArtistId/>';
    }

    $xml .= '<name>' . xmlentities($track->title) . '</name>';
    //$xml .= '<previewMp3File>' . xmlentities($track->preview_mp3_filename) . '</previewMp3File>';

    $files = AudioTrackFile::fetch_all_for_track_id($track->id, false);

    // the HQMP3 file is also used as the prelistening file
    foreach ($files as $file) {
        if ($file->type == 'HQMP3') {
            $xml .= '<previewMp3File>'  . xmlentities($file->filename) . '</previewMp3File>';
            break;
        }
    }

    foreach ($files as $file) {
        if      ($file->type == 'HQMP3')       $xml .= '<hqMp3File>' . xmlentities($file->filename) . '</hqMp3File>';
        else if ($file->type == 'AIFF')        $xml .= '<aiffFile>'  . xmlentities($file->filename) . '</aiffFile>';
        else if ($file->type == 'FLAC')        $xml .= '<flacFile>'  . xmlentities($file->filename) . '</flacFile>';
        else if ($file->type == 'OGG')         $xml .= '<oggFile>'   . xmlentities($file->filename) . '</oggFile>';
        else if ($file->type == 'WAV')         $xml .= '<wavFile>'   . xmlentities($file->filename) . '</wavFile>';
        else if ($file->type == 'ZIP')         $xml .= '<zipFile>'   . xmlentities($file->filename) . '</zipFile>';
    }

    $xml .= '<price>' . $track->price . '</price>';
    $xml .= '<currency>' . $track->currency . '</currency>';

    $xml .= '</track>';
}

?>