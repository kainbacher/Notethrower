<?php

include_once('../Includes/Init.php');
include_once('../Includes/Services_JSON.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/AudioTrackArtistVisibility.php');

include_once('../Includes/DB/Artist.php');

$artist = Artist::new_from_cookie();
if ($artist) {
    $logger->info('user is logged in');

} else {
    show_fatal_error_and_exit('access denied for not-logged-in user');
}

$artistId = get_numeric_param('aid');
$trackId  = get_numeric_param('tid');

// search for the selected artists
$artists = AudioTrackArtistVisibility::fetch_all_for_track_id($trackId);

$jsonService = new Services_JSON();
echo $jsonService->encode($artists);

?>