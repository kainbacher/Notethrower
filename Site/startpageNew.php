<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/Nonce.php');

$userIsLoggedIn = false; // FIXME
$user = null;
if ($userIsLoggedIn) {
    $user = 'FIXME'; // FIXME #####################################
}

$genre = null;
handleCurrentGenreSelection($genre);

handleVoting($user);

$leftTrack  = null;
$rightTrack = null;
$trackSelectionMessage = getLeftAndRightTrack($leftTrack, $rightTrack, $genre);

setGenreCookie($genre);

$message = '';
if ($trackSelectionMessage) {
    $message = processTpl('Common/message.html', array(
        '${msg}' => $trackSelectionMessage
    ));
}

list($nonce, $timestamp) = getNonceAndTimestamp($user);

processAndPrintTpl('Startpage/index.html', array(
    '${Common/pageHeader}'        => buildPageHeader(),
    '${selectedGenre}'            => $genre,
    '${Startpage/genreSelection}' => buildGenreSelection(),
    '${message}'                  => $message,
    '${Startpage/player_left}'    => buildLeftPlayer($leftTrack),
    '${Startpage/player_right}'   => buildRightPlayer($rightTrack),
    '${voteForLeftSongUrl}'       => $_SERVER['PHP_SELF'] . '?vt=' . $leftTrack->id . '&n=' . escape($nonce) . '&t=' . $timestamp,
    '${bothSongsAreAwfulUrl}'     => $_SERVER['PHP_SELF'],
    '${voteForRightSongUrl}'      => $_SERVER['PHP_SELF'] . '?vt=' . $rightTrack->id . '&n=' . escape($nonce) . '&t=' . $timestamp,
    '${Common/pageFooter}'        => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------
function getNonceAndTimestamp(&$user) {
    list($us, $s) = explode(' ', microtime());
    $timestamp = ($s . ($us * 1000000));

    $userId = '_not_logged_in_';
    if ($user) {
        $userId = $user->id;
    }

    return array(
        Nonce::generateNonce($userId, 'vote', $timestamp),
        $timestamp
    );
}

function handleVoting(&$user) {
    global $logger;

    $userId = '_not_logged_in_';
    if ($user) {
        $userId = $user->id;
    }

    if (!Nonce::isNonceValidAndUnused(get_param('n'), $userId, 'vote', get_param('t'))) { // don't read t param as numeric since the number might be too big
        $logger->warn('invalid nonce detected, ignoring vote');
        return;
    }

    Nonce::invalidateNonce(get_param('n'));

    $votedTrack = AudioTrack::fetch_for_id(get_numeric_param('vt'));
    if ($votedTrack) {
        $votedTrack->competition_points = $votedTrack->competition_points + 1;
        $votedTrack->save();
    }

    // FIXME - increment fan points only if fan is logged in (no na)
}

function buildLeftPlayer(&$leftTrack) {
    return processTpl('Startpage/player.html', array(
        '${trackId}'    => $leftTrack->id,
        '${mp3Url}'     => $leftTrack->getPreviewMp3Url(),
        '${compPoints}' => $leftTrack->competition_points
    ));
}

function buildRightPlayer(&$rightTrack) {
    return processTpl('Startpage/player.html', array(
        '${trackId}'    => $rightTrack->id,
        '${mp3Url}'     => $rightTrack->getPreviewMp3Url(),
        '${compPoints}' => $rightTrack->competition_points
    ));
}

function handleCurrentGenreSelection(&$genre) {
    $genre = getGenreCookieValue();

    if ($selectedGenre = get_param('genre')) {
        if (!isValidGenre($selectedGenre)) {
            show_fatal_error_and_exit('invalid genre value: ' . $selectedGenre);
        }

        $genre = $selectedGenre;

    } else {
        if (!$genre) {
            $genre = chooseRandomGenre();
        }
    }
}

function getLeftAndRightTrack(&$leftTrack, &$rightTrack, &$genre) {
    global $logger;

    $leftTrack  = AudioTrack::fetchRandomPublicTrack($genre);
    $rightTrack = AudioTrack::fetchRandomPublicTrack($genre, $leftTrack->id); // ensure that we have different tracks
    $retryCount = 0;
    $trackSelectionMessage = null;
    while ((!$leftTrack || !$rightTrack) && $retryCount <= 100) { // search until we have two different tracks in the same genre
        $trackSelectionMessage = 'Sorry but we couldn\'t find two songs in the selected genre. A random genre was chosen.';
        $retryCount++;
        $genre = chooseRandomGenre();
        $leftTrack  = AudioTrack::fetchRandomPublicTrack($genre);
        $rightTrack = AudioTrack::fetchRandomPublicTrack($genre, $leftTrack->id); // ensure that we have different tracks
    }

    if (!$leftTrack || !$rightTrack) {
        show_fatal_error_and_exit('unable to find two different tracks in the same genre, giving up.');
    }

    return $trackSelectionMessage;
}

function buildGenreSelection() {
    $genreList = '';
    foreach ($GLOBALS['GENRES'] as $g) {
        $genreList .= processTpl('Startpage/genreSelectionElement.html', array(
            '${genreSelectionUrl}' => $_SERVER['PHP_SELF'] . '?genre=' . urlencode($g),
            '${genre}'             => escape($g)
        ));
    }

    return processTpl('Startpage/genreSelection.html', array(
        '${genreSelectionElement_list}' => $genreList
    ));
}

function buildPageHeader() {
    return processTpl('Common/pageHeader.html', array(
        '${pageTitleSuffix}' => ' - Start'
    ));
}

function buildPageFooter() {
    return processTpl('Common/pageFooter.html', array());
}

?>