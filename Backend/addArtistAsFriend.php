<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
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

$artistId = get_numeric_param('aid');
$trackId  = get_numeric_param('tid');

// check permissions
$track = AudioTrack::fetch_for_id($trackId);
if (!$track) {
    show_fatal_error_and_exit('no track found for id: ' . $trackId);

} else {
    if ($track->artist_id != $loggedInArtist->id) {
        show_fatal_error_and_exit('track ' . $trackId . ' does not belong to artist ' . $loggedInArtist->id);
    }
}

// add the artist to the given track
$atav = new AudioTrackArtistVisibility();
$atav->track_id  = $trackId;
$atav->artist_id = $artistId;
$atav->save();

echo 'Success';

?>