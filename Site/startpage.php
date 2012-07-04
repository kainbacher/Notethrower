<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/RemoteSystemCommunicationUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Genre.php');
include_once('../Includes/DB/Nonce.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/User.php');

$messages = '';

$user = User::new_from_cookie();

$genre = handleCurrentGenreSelection();

handleVoting($user, $messages);

$leftTrack  = null;
$rightTrack = null;
$trackSelectionMessage = getLeftAndRightTrack($leftTrack, $rightTrack, $genre);

setGenreCookie($genre);

if ($trackSelectionMessage) {
    $messages .= processTpl('Common/message_notice.html', array(
        '${msg}' => $trackSelectionMessage
    ));
}

list($nonce, $timestamp) = getNonceAndTimestamp($user);

$leftTrackId  = null;
$rightTrackId = null;
if ($leftTrack)  $leftTrackId  = $leftTrack->id;
if ($rightTrack) $rightTrackId = $rightTrack->id;

processAndPrintTpl('Startpage/index.html', array(
    '${Common/pageHeader}'                    => buildPageHeader('Start', true, false),
    '${Common/bodyHeader}'                    => buildBodyHeader($user),
    '${selectedGenre}'                        => $genre,
    '${Startpage/genreSelectionElement_list}' => buildGenreSelectionList(),
    '${Common/message_choice_list}'           => $messages,
    '${Startpage/player_left}'                => buildLeftPlayer($leftTrack),
    '${Startpage/player_right}'               => buildRightPlayer($rightTrack),
    '${voteForLeftSongUrl}'                   => basename($_SERVER['PHP_SELF'], '.php') . '?vt=' . $leftTrackId  . '&lt=' . $leftTrackId . '&rt=' . $rightTrackId . '&n=' . escape($nonce) . '&t=' . $timestamp,
    '${bothSongsAreAwfulUrl}'                 => basename($_SERVER['PHP_SELF'], '.php'),
    '${voteForRightSongUrl}'                  => basename($_SERVER['PHP_SELF'], '.php') . '?vt=' . $rightTrackId . '&lt=' . $leftTrackId . '&rt=' . $rightTrackId . '&n=' . escape($nonce) . '&t=' . $timestamp,
    '${Common/bodyFooter}'                    => buildBodyFooter(),
    '${Common/pageFooter}'                    => buildPageFooter()
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

function handleVoting(&$user, &$messages) {
    global $logger;

    if (!isParamSet('vt')) return;

    $userId = '_not_logged_in_';
    if ($user) {
        $userId = $user->id;
    }

    if (!Nonce::isNonceValidAndUnused(get_param('n'), $userId, 'vote', get_param('t'))) { // don't read t param as numeric since the number might be too big
        $logger->warn('invalid nonce detected, ignoring vote');
        return;
    }

    Nonce::invalidateNonce(get_param('n'));

    $votedTrack = Project::fetch_for_id(get_numeric_param('vt'));
    if ($votedTrack) {
        $votedTrack->competition_points = $votedTrack->competition_points + 1;
        $votedTrack->save();
    }

    if (!$user) {
        $messages .= processTpl('Common/message_notice.html', array(
            '${msg}' => 'You would have just earned x points but we don\'t know who you are.<br />Sign in to find out what you can do with these points!'
        ));
    }

    // FIXME - increment fan points only if fan is logged in (no na)
}

function buildLeftPlayer(&$leftTrack) {
    return processTpl('Startpage/player.html', array(
        '${playerId}'   => 1,
        '${trackId}'    => $leftTrack ? $leftTrack->id : null,
        '${trackTitle}' => $leftTrack ? $leftTrack->title : 'n/a',
        '${mp3Url}'     => $leftTrack ? $leftTrack->getPreviewMp3Url() : '',
        '${compPoints}' => $leftTrack ? $leftTrack->competition_points : null
    ));
}

function buildRightPlayer(&$rightTrack) {
    return processTpl('Startpage/player.html', array(
        '${playerId}'   => 2,
        '${trackId}'    => $rightTrack ? $rightTrack->id : null,
        '${trackTitle}' => $rightTrack ? $rightTrack->title : 'n/a',
        '${mp3Url}'     => $rightTrack ? $rightTrack->getPreviewMp3Url() : '',
        '${compPoints}' => $rightTrack ? $rightTrack->competition_points : null
    ));
}

function handleCurrentGenreSelection() {
    $genre = getGenreCookieValue();

    if ($selectedGenre = get_param('genre')) {
        if (!Genre::isValidGenre($selectedGenre)) {
            show_fatal_error_and_exit('invalid genre value: ' . $selectedGenre);
        }

        $genre = $selectedGenre;

    } else {
        if (!$genre) {
            $genre = Genre::chooseRandomGenreName();
        }
    }

    return $genre;
}

function getLeftAndRightTrack(&$leftTrack, &$rightTrack, &$genre) {
    global $logger;

    $leftTrack  = Project::fetchRandomPublicFinishedProject($genre);
    $rightTrack = Project::fetchRandomPublicFinishedProject($genre, $leftTrack->id); // ensure that we have different tracks
    $retryCount = 0;
    $trackSelectionMessage = null;
    while ((!$leftTrack || !$rightTrack) && $retryCount <= 100) { // search until we have two different tracks in the same genre
        $trackSelectionMessage = 'Sorry but we couldn\'t find two songs in the selected genre. A random genre was chosen.';
        $retryCount++;
        $genre = Genre::chooseRandomGenreName();
        $leftTrack  = Project::fetchRandomPublicFinishedProject($genre);
        $rightTrack = Project::fetchRandomPublicFinishedProject($genre, $leftTrack->id); // ensure that we have different tracks
    }

    if (!$leftTrack || !$rightTrack) {
        $logger->warn('unable to find two different tracks in the same genre, giving up.');
        $trackSelectionMessage = 'No tracks found.';
        $leftTrack  = null;
        $rightTrack = null;
    }

    return $trackSelectionMessage;
}

function buildGenreSelectionList() {
    $genreList = '';
    $genres = Genre::fetchAll();
    foreach ($genres as $g) {
        $genreList .= processTpl('Startpage/genreSelectionElement.html', array(
            '${genreSelectionUrl}' => $_SERVER['PHP_SELF'] . '?genre=' . urlencode($g->name),
            '${genre}'             => escape($g->name)
        ));
    }

    return $genreList;
}

?>
