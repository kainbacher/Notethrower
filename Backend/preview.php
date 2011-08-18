<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Logger.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');

$logger->debug('song: ' . get_param('song'));

$partial_delivery = false;

$filepath = $GLOBALS['CONTENT_BASE_PATH'] . get_param('song');
$track_id = get_numeric_param('tid');

if ($track_id) {
    $track = Project::fetch_for_id($track_id);
    if ($track) {
        $track->playback_count = $track->playback_count + 1;
        $track->save();
        $logger->info('increased playback count for track id: ' . $track_id);
    }
}

if ($partial_delivery) {
    $length = (int) (filesize($filepath) * 0.25); // deliver only 25% of the song
    $content = substr(file_get_contents($filepath), 0, $length);

} else {
    $length = filesize($filepath);
    $content = file_get_contents($filepath);
}

header('Content-Type: audio/mp3');
header('Content-Length: ' . $length);

// deliver preview content
echo $content;

?>


