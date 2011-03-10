<?php

include_once('../Includes/Init.php');
include_once('../Includes/Paginator.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/News.php');

$loginErrorMsg = '';

$visitorUserId = -1;

$userIsLoggedIn = false;
$user = User::new_from_cookie();
if ($user) {
    $visitorUserId = $user->id;
    $logger->info('visitor user id: ' . $visitorUserId);

    $userIsLoggedIn = true;
    $logger->info('user is logged in');
}

$pageNum = get_numeric_param('page');

$trackCount = AudioTrack::count_all(false, false, $visitorUserId);
$logger->info('track count: ' . $trackCount);

$paginatorResp = paginator_get_start_and_end_item_for_page($trackCount, 16, $pageNum);

$mode = get_param('mode');
if (!$mode) {
    $mode = 'mostRecent';
}

if ($user && $mode == 'privateTracks') {
    $tracks = AudioTrack::fetch_all_private_tracks_the_user_can_access($paginatorResp['startItem'], $paginatorResp['endItem'], $user->id);

} else {
    if ($mode == 'mostRecent') {
        $tracks = AudioTrack::fetch_newest_from_to($paginatorResp['startItem'], $paginatorResp['endItem'], false, false, $visitorUserId);
    } else { // mostDownloaded
        $tracks = AudioTrack::fetch_most_downloaded_from_to($paginatorResp['startItem'], $paginatorResp['endItem'], false, false, $visitorUserId);
    }
}

echo '<table class="trackGridTable">';


$i = 0;
foreach ($tracks as $track) {
    if ($i % 4 == 0) {
        echo '<tr>';
    }

    echo '<td class="trackGridElt cursorHand" onClick="featuredTrackClicked(' . $track->id . '); reloadDataInWidget(' . $track->user_id . ', ' . $track->id . ');"><div class="tlContainer">';

    if ($track->user_img_filename) {
        $filename = str_replace('.jpg', '_thumb.jpg', $track->user_img_filename);
        $userImg    = $GLOBALS['USER_IMAGE_BASE_PATH'] . $filename;
        $userImgUrl = $GLOBALS['USER_IMAGE_BASE_URL']  . $filename;

        //$logger->debug('user img: ' . $userImg);
        if (file_exists($userImg)) {
            echo '<div class="tlUserImg"><img src="' . $userImgUrl . '"></div>';
        } else {
            echo '<div class="tlUserImg"><img src="../Images/no_artist_image.png"></div>';
        }

    } else {
        echo '<div class="tlUserImg"><img src="../Images/no_artist_image.png"></div>';
    }

    echo '<div class="tlOverlayText">' . "\n";
    echo '<div class="tlotTrack">'  . $track->title       . '</div>';
    echo '<div class="tlotUser">' . $track->user_name . '</div>';
    echo '</div>' . "\n";

    echo '</div></td>' . "\n";

    if ($i % 4 == 3) {
        echo '</tr>' . "\n";
    }

    $i++;
}

if ($i < 16) { // if grid was not fully loaded with tracks, fill up with empty cells
	for (; $i < 16; $i++) {

        if ($i % 4 == 0) {
            echo '<tr>';
        }

        echo '<td class="emptyTrackGridElt"><div class="tlContainer">';
        echo '<div class="tlUserImg">&nbsp;</div>';


        echo '<div class="tlOverlayText">' . "\n";
        echo '<div class="tlotTrack">&nbsp;</div>';
        echo '<div class="tlotUser">&nbsp;</div>';
        echo '</div>' . "\n";

        echo '</div></td>' . "\n";

        if ($i % 4 == 3) {
            echo '</tr>' . "\n";
        }

    }
}

echo '</table>';