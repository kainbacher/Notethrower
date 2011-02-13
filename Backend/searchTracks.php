<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Services_JSON.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/AudioTrack.php');

$jsonService = new Services_JSON();

$from = get_numeric_param('iDisplayStart');
$length = get_numeric_param('iDisplayLength');

$visitorArtistId = get_numeric_param('vaid');

$artistOrTitle = get_param('artistOrTitle');
$needsAttributIds = get_param('needsAttributIds');
$containsAttributIds = get_param('containsAttributIds');
$containsOthers = get_param('containsOthers');
$needsOthers = get_param('needsOthers');
$genres = get_param('genres') ? explode(',', get_param('genres')) : array();

$logger->info(print_r($genres, true));

$tracks = AudioTrack::fetchForSearch($from, $length, $artistOrTitle, $needsAttributIds, $containsAttributIds, $needsOthers, $containsOthers, $genres, false, false, $visitorArtistId);
$filteredTracksCount = AudioTrack::fetchCountForSearch($artistOrTitle, $needsAttributIds, $containsAttributIds, $needsOthers, $containsOthers, $genres, false, false, $visitorArtistId);

$logger->info('finished db search');

$trackData->sEcho = get_param('sEcho');
$trackData->iTotalRecords = $filteredTracksCount;
$trackData->iTotalDisplayRecords = $filteredTracksCount;
$trackData->aaData = array();

$i=0;
foreach ($tracks as $track) {
    if ($track->artist_img_filename) {
        $filename = str_replace('.jpg', '_thumb.jpg', $track->artist_img_filename);
        $artistImg    = $GLOBALS['ARTIST_IMAGE_BASE_PATH'] . $filename;
        $artistImgUrl = $GLOBALS['ARTIST_IMAGE_BASE_URL']  . $filename;
    
        if (file_exists($artistImg)) {
            $track->artist_img_filename =  $artistImgUrl;
        } else {
            $track->artist_img_filename = '../Images/no_artist_image.png';
        }
    } else {
        $track->artist_img_filename = '../Images/no_artist_image.png';
    }
    
    $trackData->aaData[$i] = array($track->artist_img_filename, $track->artist_name, $track->title, $track->id, $track->artist_id);
    $i = $i + 1;   
}

echo $jsonService->encode($trackData);

?>