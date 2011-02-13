<?php

include_once('../Includes/Snippets.php');
include_once('../Includes/DB/AudioTrack.php');

function ensureArtistIsLoggedIn($artist) {
    global $logger;

    if ($artist) {
        $logger->info('user is logged in');

    } else {
        $logger->info('user is NOT logged in');
        header('Location: pleaseLogin.php');
        exit;
    }
}

function ensureTrackIdBelongsToArtistId($trackId, $artistId) {
    if (!$trackId) {
        show_fatal_error_and_exit('Track ID not specified!');
    }

    $track = AudioTrack::fetch_for_id($trackId);

    if (!$track || !$track->id) {
        show_fatal_error_and_exit('Track with ID ' . $trackId . ' not found!');
    }

    ensureTrackBelongsToArtistId($track, $artistId);
}

function ensureTrackBelongsToArtistId($track, $artistId) {
    if (!$track || !$track->id) {
        show_fatal_error_and_exit('Track object not specified!');
    }

    if (!$artistId) {
        show_fatal_error_and_exit('Artist ID not specified!');
    }

    if ($track->artist_id != $artistId) {
        show_fatal_error_and_exit('Track with ID ' . $track->id . ' does not belong to artist with ID ' .
                $artistId . ' (owner artist ID: ' . $track->artist_id . ')!');
    }
}

function ensureMessageIdBelongsToArtist($mid, $artist) {
    if (!$mid) {
        show_fatal_error_and_exit('Msg ID not specified!');
    }

    if (!$artist || !$artist->id) {
        show_fatal_error_and_exit('Artist not specified!');
    }

    $msg = Message::fetch_for_id($mid);

    if (!$msg || !$msg->id) {
        show_fatal_error_and_exit('Message with ID ' . $mid . ' not found!');
    }

    ensureMessageBelongsToArtist($msg, $artist);
}

function ensureMessageBelongsToArtist($msg, $artist) {
    if (!$msg || !$msg->id) {
        show_fatal_error_and_exit('Msg not specified!');
    }

    if (!$artist || !$artist->id) {
        show_fatal_error_and_exit('Artist not specified!');
    }

    if ($msg->recipient_artist_id != $artist->id) {
        show_fatal_error_and_exit('Msg with ID ' . $msg->id . ' does not belong to artist with ID ' .
                $artist->id . ' (owner artist ID: ' . $msg->recipient_artist_id . ')!');
    }
}

?>