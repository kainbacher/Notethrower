<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/ProjectFile.php');
include_once('../Includes/DB/Stats.php');

// let's see if the visiting user is a logged in user
$visitorUserId = -1;
$visitorUser = User::new_from_cookie();
if ($visitorUser) {
    $visitorUserId = $visitorUser->id;
    $logger->info('visitor user id: ' . $visitorUserId);
}

$uid = get_numeric_param('aid');

if (!$uid) {
    show_fatal_error_and_exit('User ID missing!');
}

$user = User::fetch_for_id($uid);

if (!$user || !$user->id) {
    show_fatal_error_and_exit('User not found!');
}

$xml = '<?xml version="1.0" encoding="UTF-8" ?>';
$xml .= '<ppArtistData>';
$xml .= '<id>' . $user->id . '</id>';
$xml .= '<name>' . xmlentities($user->name) . '</name>';
$xml .= '<imageFile>' . xmlentities($user->image_filename) . '</imageFile>';
//$xml .= '<webpageUrl>' . xmlentities($user->webpage_url) . '</webpageUrl>';
$xml .= '<webpageUrl>' . xmlentities($GLOBALS['BASE_URL'] . 'Site/userInfo.php?aid=' . $user->id) . '</webpageUrl>';
$xml .= '<tracks>';

// load originals
$tracks = AudioTrack::fetch_all_originals_of_user_id_from_to($uid, 0, 9999, false, false, $visitorUserId); // FIXME - paging - use Paginator
foreach ($tracks as $track) {
    processTrack($xml, $track, false);
}

// load the remixes
$tracks = AudioTrack::fetch_all_remixes_of_user_id_from_to($uid, 0, 9999, false, false, $visitorUserId); // FIXME - paging - use Paginator
foreach ($tracks as $track) {
    processTrack($xml, $track, true);
}

// load the songs which were remixed by others
$tracks = AudioTrack::fetch_all_remixes_for_originating_user_id_from_to($uid, 0, 9999, false, false, $visitorUserId); // FIXME - paging - use Paginator
foreach ($tracks as $track) {
    processTrack($xml, $track, true);
}

$xml .= '</tracks>';
$xml .= '</ppArtistData>';

header('Content-type: text/xml; charset=UTF-8');
header('Content-length: ' . strlen($xml));
echo $xml;

// record the access
if ($user->id && $_SERVER['REMOTE_ADDR']) {
    $stats = new Stats();
    $stats->user_id = $user->id;
    $stats->ip        = $_SERVER['REMOTE_ADDR'];
    $stats->insert();
}

function processTrack(&$xml, &$track, $remixedByOthersMode) {
    $xml .= '<track>';

    $xml .= '<id>' . $track->id . '</id>';
    $xml .= '<type>' . $track->type . '</type>';

    if ($track->type == 'remix') {
        $xml .= '<remixerArtistId>' . $track->user_id . '</remixerArtistId>';
        $xml .= '<remixerArtistName>' . $track->user_name . '</remixerArtistName>';
        $xml .= '<originatingArtistId>' . $track->originating_user_id . '</originatingArtistId>';
        $xml .= '<originatingArtistName>' . $track->originating_user_name . '</originatingArtistName>';

    } else {
        $xml .= '<remixerArtistId/>';
        $xml .= '<originatingArtistId/>';
    }

    $xml .= '<name>' . xmlentities($track->title) . '</name>';
    //$xml .= '<previewMp3File>' . xmlentities($track->preview_mp3_filename) . '</previewMp3File>';

    $files = ProjectFile::fetch_all_for_track_id($track->id, false);

    // the HQMP3 file is also used as the prelistening file
    foreach ($files as $file) {
        $filename = strtolower($file->orig_filename);

        if (strpos($filename, '.mp3') !== false) {
            $xml .= '<previewMp3File>'  . xmlentities($file->filename) . '</previewMp3File>';
            break;
        }
    }

    foreach ($files as $file) {
        $filename = strtolower($file->orig_filename);

        if      (strpos($filename, '.mp3')  !== false) $xml .= '<hqMp3File>' . xmlentities($file->filename) . '</hqMp3File>';
        else if (strpos($filename, '.aiff') !== false) $xml .= '<aiffFile>'  . xmlentities($file->filename) . '</aiffFile>';
        else if (strpos($filename, '.flac') !== false) $xml .= '<flacFile>'  . xmlentities($file->filename) . '</flacFile>';
        else if (strpos($filename, '.ogg')  !== false) $xml .= '<oggFile>'   . xmlentities($file->filename) . '</oggFile>';
        else if (strpos($filename, '.wav')  !== false) $xml .= '<wavFile>'   . xmlentities($file->filename) . '</wavFile>';
        else if (strpos($filename, '.zip')  !== false) $xml .= '<zipFile>'   . xmlentities($file->filename) . '</zipFile>';
    }

    $xml .= '<price>' . $track->price . '</price>';
    $xml .= '<currency>' . $track->currency . '</currency>';

    $xml .= '</track>';
}

?>