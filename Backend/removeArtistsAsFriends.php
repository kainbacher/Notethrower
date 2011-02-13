<?php

include_once('../Includes/Init.php');

include_once('../Includes/Snippets.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/AudioTrackArtistVisibility.php');
include_once('../Includes/DB/Artist.php');

$loggedInArtist = Artist::new_from_cookie();
if ($loggedInArtist) {
    $logger->info('user is logged in');

} else {
    show_fatal_error_and_exit('access denied for not-logged-in user');
}

$logger->debug('cp1');

$trackId         = get_numeric_param('tid');
$artistIdListStr = get_param('aids');

$logger->debug('cp2');

// check permissions
$track = AudioTrack::fetch_for_id($trackId);
$logger->debug('cp3');
if (!$track) {
    $logger->debug('cp4');
    show_fatal_error_and_exit('no track found for id: ' . $trackId);

} else {
    $logger->debug('cp5');
    if ($track->artist_id != $loggedInArtist->id) {
        $logger->debug('cp6');
        show_fatal_error_and_exit('track ' . $trackId . ' does not belong to artist ' . $loggedInArtist->id);
    }
}

$logger->debug('cp7');

// remove the artists with the specified ids (e.g. 12,13,43)
AudioTrackArtistVisibility::delete_all_with_track_id_and_artist_id_list($trackId, explode(',', $artistIdListStr));

$logger->debug('cp8');

echo 'Success';

?>