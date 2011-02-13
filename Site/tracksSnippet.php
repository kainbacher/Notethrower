<?php

include_once('../Includes/Init.php');
include_once('../Includes/Paginator.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/Artist.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/News.php');

$loginErrorMsg = '';

$visitorArtistId = -1;

$userIsLoggedIn = false;
$artist = Artist::new_from_cookie();
if ($artist) {
    $visitorArtistId = $artist->id;
    $logger->info('visitor artist id: ' . $visitorArtistId);

    $userIsLoggedIn = true;
    $logger->info('user is logged in');
}

$pageNum = get_numeric_param('page');

$trackCount = AudioTrack::count_all(false, false, $visitorArtistId);
$logger->info('track count: ' . $trackCount);

$paginatorResp = paginator_get_start_and_end_item_for_page($trackCount, 16, $pageNum);

$mode = get_param('mode');
if (!$mode) {
    $mode = 'mostRecent';
}

if ($artist && $mode == 'privateTracks') {
    $tracks = AudioTrack::fetch_all_private_tracks_the_artist_can_access($paginatorResp['startItem'], $paginatorResp['endItem'], $artist->id);

} else {
    if ($mode == 'mostRecent') {
        $tracks = AudioTrack::fetch_newest_from_to($paginatorResp['startItem'], $paginatorResp['endItem'], false, false, $visitorArtistId);
    } else { // mostDownloaded
        $tracks = AudioTrack::fetch_most_downloaded_from_to($paginatorResp['startItem'], $paginatorResp['endItem'], false, false, $visitorArtistId);
    }
}

echo '<table class="trackGridTable">';


$i = 0;
foreach ($tracks as $track) {
    if ($i % 4 == 0) {
        echo '<tr>';
    }

    echo '<td class="trackGridElt cursorHand" onClick="featuredTrackClicked(' . $track->id . '); reloadDataInWidget(' . $track->artist_id . ', ' . $track->id . ');"><div class="tlContainer">';

    if ($track->artist_img_filename) {
        $filename = str_replace('.jpg', '_thumb.jpg', $track->artist_img_filename);
        $artistImg    = $GLOBALS['ARTIST_IMAGE_BASE_PATH'] . $filename;
        $artistImgUrl = $GLOBALS['ARTIST_IMAGE_BASE_URL']  . $filename;

        //$logger->debug('artist img: ' . $artistImg);
        if (file_exists($artistImg)) {
            echo '<div class="tlArtistImg"><img src="' . $artistImgUrl . '"></div>';
        } else {
            echo '<div class="tlArtistImg"><img src="../Images/no_artist_image.png"></div>';
        }

    } else {
        echo '<div class="tlArtistImg"><img src="../Images/no_artist_image.png"></div>';
    }

    echo '<div class="tlOverlayText">' . "\n";
    echo '<div class="tlotTrack">'  . $track->title       . '</div>';
    echo '<div class="tlotArtist">' . $track->artist_name . '</div>';
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
        echo '<div class="tlArtistImg">&nbsp;</div>';


        echo '<div class="tlOverlayText">' . "\n";
        echo '<div class="tlotTrack">&nbsp;</div>';
        echo '<div class="tlotArtist">&nbsp;</div>';
        echo '</div>' . "\n";

        echo '</div></td>' . "\n";

        if ($i % 4 == 3) {
            echo '</tr>' . "\n";
        }

    }
}

echo '</table>';