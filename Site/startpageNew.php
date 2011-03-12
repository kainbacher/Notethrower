<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/RemoteSystemCommunicationUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/Nonce.php');
include_once('../Includes/DB/User.php');

$message = '';

$user = handleAuthentication(&$message);

$genre = null;
handleCurrentGenreSelection($genre);

handleVoting($user);

$leftTrack  = null;
$rightTrack = null;
$trackSelectionMessage = getLeftAndRightTrack($leftTrack, $rightTrack, $genre);

setGenreCookie($genre);

if ($trackSelectionMessage) {
    $message = processTpl('Common/message.html', array(
        '${msg}' => $trackSelectionMessage
    ));
}

list($nonce, $timestamp) = getNonceAndTimestamp($user);

$loggedInUserInfoBlock = '';
$loginBlock = '';
if (!$user) {
    $loggedInUserInfoBlock = buildLoggedInUserInfoBlock($user);
} else {
    $loginBlock = buildLoginBlock();
}

processAndPrintTpl('Startpage/index.html', array(
    '${Common/pageHeader}'                      => buildPageHeader(),
    '${Startpage/login_optional}'               => $loginBlock,
    '${Startpage/loggedInUserInfo_optional}'    => $loggedInUserInfoBlock,
    '${selectedGenre}'                          => $genre,
    '${Startpage/genreSelectionElement_list}'   => buildGenreSelectionList(),
    '${message}'                                => $message,
    '${Startpage/player_left}'                  => buildLeftPlayer($leftTrack),
    '${Startpage/player_right}'                 => buildRightPlayer($rightTrack),
    '${voteForLeftSongUrl}'                     => $_SERVER['PHP_SELF'] . '?vt=' . $leftTrack->id  . '&lt=' . $leftTrack->id . '&rt=' . $rightTrack->id . '&n=' . escape($nonce) . '&t=' . $timestamp,
    '${bothSongsAreAwfulUrl}'                   => $_SERVER['PHP_SELF'],
    '${voteForRightSongUrl}'                    => $_SERVER['PHP_SELF'] . '?vt=' . $rightTrack->id . '&lt=' . $leftTrack->id . '&rt=' . $rightTrack->id . '&n=' . escape($nonce) . '&t=' . $timestamp,
    '${Common/pageFooter}'                      => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------
function handleAuthentication(&$loginErrorMsg) {
    // check if user is logged in
    $user = User::new_from_cookie();
    if ($user) return $user; // nothing more to do here, the user is logged in.

    // check if user is about to login (the regular way)
    if (get_param('action') == 'login') {
        $logger->info('login request received');
        if (get_param('user') && get_param('password')) {
            $user = User::fetch_for_username_password(get_param('user'), get_param('password'));
            if ($user && $user->status == 'active') {
                $user->doLogin();
                $logger->info('login successful, reloading page to set cookie');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;

            } else {
                $loginErrorMsg = 'Login failed! Please try again.';
                $logger->info('login failed');
                return null;
            }

        } else {
            $loginErrorMsg = 'Please provide a username and password!';
            $logger->info('username and/or password missing');
            return null;
        }
    }

    // check if user data can be fetched from facebook
    if (get_param('access_token')) {
        $resp = sendGetRequest('https://graph.facebook.com/me?access_token=' . get_param('access_token'), 15);
        if ($resp['result'] == 'SUCCESS') {
            $logger->info(print_r(json_decode($resp['responseBody']), true));

            // FIXME ##################

            // if user data complete, log user in FIXME
            $user->doLogin();
            $logger->info('facebook login successful, user data is complete, reloading page to set cookie');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;

            // if user data incomplete, show full registration page FIXME

            return null; // FIXME

        } else {
            $logger->error('failed to get user information from facebook: ' . $resp['error']);
        }
    }

    return null;
}

function buildLoginBlock() {
    global $logger;

    $loginBlock = processTpl('Startpage/login.html', array(
        '${facebookAppId}' => $GLOBALS['FACEBOOK_APP_ID'],
    ));

    return $loginBlock;
}

function buildLoggedInUserInfoBlock(&$user) {
    global $logger;

    $block = processTpl('Startpage/loggedInUserInfo.html', array(
        '${userName}' => $user->name ? $user->name : $user->username,
    ));

    return $block;
}

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

function buildGenreSelectionList() {
    $genreList = '';
    foreach ($GLOBALS['GENRES'] as $g) {
        $genreList .= processTpl('Startpage/genreSelectionElement.html', array(
            '${genreSelectionUrl}' => $_SERVER['PHP_SELF'] . '?genre=' . urlencode($g),
            '${genre}'             => escape($g)
        ));
    }

    return $genreList;
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