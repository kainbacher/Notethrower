<?php

include_once('../Includes/Init.php');

include_once('../Includes/Config.php');
include_once('../Includes/Services_JSON.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Project.php');

$jsonService = new Services_JSON();

$from = get_numeric_param('iDisplayStart');
$length = get_numeric_param('iDisplayLength');

$visitorUserId = get_numeric_param('vaid');

$userOrTitle = get_param('artistOrTitle');
$needsAttributIds = get_param('needsAttributIds');
$containsAttributIds = get_param('containsAttributIds');
$containsOthers = get_param('containsOthers');
$needsOthers = get_param('needsOthers');
$genres = get_param('genres') ? explode(',', get_param('genres')) : array();

$logger->info('genre-ids: ' . print_r($genres, true));

$tracks = Project::fetchForSearch($from, $length, $userOrTitle, $needsAttributIds, $containsAttributIds, $needsOthers, $containsOthers, $genres, false, false, $visitorUserId);
$filteredTracksCount = Project::fetchCountForSearch($userOrTitle, $needsAttributIds, $containsAttributIds, $needsOthers, $containsOthers, $genres, false, false, $visitorUserId);

$logger->info('finished db search');

$trackData->sEcho = get_param('sEcho');
$trackData->iTotalRecords = $filteredTracksCount;
$trackData->iTotalDisplayRecords = $filteredTracksCount;
$trackData->aaData = array();

$i=0;
foreach ($tracks as $track) {
    if ($track->user_img_filename) {
        $filename = str_replace('.jpg', '_thumb.jpg', $track->user_img_filename);
        $userImg    = $GLOBALS['USER_IMAGE_BASE_PATH'] . $filename;
        $userImgUrl = $GLOBALS['USER_IMAGE_BASE_URL']  . $filename;

        if (file_exists($userImg)) {
            $track->user_img_filename =  $userImgUrl;
        } else {
            $track->user_img_filename = '../Images/no_artist_image.png';
        }
    } else {
        $track->user_img_filename = '../Images/no_artist_image.png';
    }

    $trackData->aaData[$i] = array($track->user_img_filename, $track->user_name, $track->title, $track->id, $track->user_id);
    $i = $i + 1;
}

echo $jsonService->encode($trackData);

?>