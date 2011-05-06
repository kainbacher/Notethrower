<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/RemoteSystemCommunicationUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/AudioTrack.php');
include_once('../Includes/DB/Nonce.php');
include_once('../Includes/DB/User.php');

$messages = '';

$user = handleAuthentication($messages);

$loggedInUserInfoBlock = '';
$loginBlock = '';
if ($user) {
    $loggedInUserInfoBlock = buildLoggedInUserInfoBlock($user);
} else {
    $loginBlock = buildLoginBlock();
}

$genre = null;
handleCurrentGenreSelection($genre);

handleVoting($user, $messages);

$leftTrack  = null;
$rightTrack = null;
$trackSelectionMessage = getLeftAndRightTrack($leftTrack, $rightTrack, $genre);

setGenreCookie($genre);

if ($trackSelectionMessage) {
    $messages .= processTpl('Common/message.html', array(
        '${msg}' => $trackSelectionMessage
    ));
}

list($nonce, $timestamp) = getNonceAndTimestamp($user);

processAndPrintTpl('Startpage/index.html', array(
    '${Common/pageHeader}'                    => buildPageHeader('Start', true, false),
    '${Startpage/login_optional}'             => $loginBlock,
    '${Startpage/loggedInUserInfo_optional}'  => $loggedInUserInfoBlock,
    '${selectedGenre}'                        => $genre,
    '${Startpage/genreSelectionElement_list}' => buildGenreSelectionList(),
    '${Common/message_list}'                  => $messages,
    '${Startpage/player_left}'                => buildLeftPlayer($leftTrack),
    '${Startpage/player_right}'               => buildRightPlayer($rightTrack),
    '${voteForLeftSongUrl}'                   => $_SERVER['PHP_SELF'] . '?vt=' . $leftTrack->id  . '&lt=' . $leftTrack->id . '&rt=' . $rightTrack->id . '&n=' . escape($nonce) . '&t=' . $timestamp,
    '${bothSongsAreAwfulUrl}'                 => $_SERVER['PHP_SELF'],
    '${voteForRightSongUrl}'                  => $_SERVER['PHP_SELF'] . '?vt=' . $rightTrack->id . '&lt=' . $leftTrack->id . '&rt=' . $rightTrack->id . '&n=' . escape($nonce) . '&t=' . $timestamp,
    '${Common/pageFooter}'                    => buildPageFooter()
));

// END

// functions
// -----------------------------------------------------------------------------
function handleAuthentication(&$messages) {
    global $logger;

    // check if user is logged in
    $user = User::new_from_cookie();
    if ($user) {
        $logger->info('user cookie found');
        return $user; // nothing more to do here, the user is logged in.
    }

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
                $messages .= processTpl('Common/message.html', array(
                    '${msg}' => 'Login failed! Please try again.'
                ));
                $logger->info('login failed');
                return null;
            }

        } else {
            $messages .= processTpl('Common/message.html', array(
                '${msg}' => 'Please provide a username and password!'
            ));
            $logger->info('username and/or password missing');
            return null;
        }
    }

    // check if user data can be fetched from facebook
    if (get_param('access_token')) {
        $logger->info('access_token param received');
        $resp = sendGetRequest('https://graph.facebook.com/me?access_token=' . get_param('access_token'), 15);
        if ($resp['result'] == 'SUCCESS') {
            $fbUserData = json_decode($resp['responseBody']);
            $logger->debug(print_r($fbUserData, true));

            // if user data complete, log user in
            $user = User::fetch_for_email_address($fbUserData->email); // ####### FIXME there are some duplicated emails in the NT database!
            if ($user) {
                $user->doLogin();
                $logger->info('facebook login successful, reloading page to set cookie');
                redirectTo($_SERVER['PHP_SELF']);

            } else { // user not found -> this either means the user doesn't exist here yet or he was not found with the facebook email address
                // the only thing we can do here is redirect the user to the signup page
                // FIXME - which page mode? fan or artist or shall we redirect to the selection page (as soon as we have one)?
                redirectTo('createUser.php?email_address=' . urlencode($fbUserData->email) . '&username=' . urlencode($fbUserData->email));
            }

        } else {
            $messages .= processTpl('Common/message.html', array(
                '${msg}' => 'Failed to get user information from facebook! Please try again later.'
            ));
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
        '${userName}' => ($user->name ? $user->name : $user->username)
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

    $votedTrack = AudioTrack::fetch_for_id(get_numeric_param('vt'));
    if ($votedTrack) {
        $votedTrack->competition_points = $votedTrack->competition_points + 1;
        $votedTrack->save();
    }

    if (!$user) {
        $messages .= processTpl('Common/message.html', array(
            '${msg}' => 'You would have just earned x points but we don\'t know who you are.<br />Sign in to find out what you can do with these points!'
        ));
    }

    // FIXME - increment fan points only if fan is logged in (no na)
}

function buildLeftPlayer(&$leftTrack) {
    return processTpl('Startpage/player.html', array(
        '${playerId}'   => 1,
        '${trackId}'    => $leftTrack->id,
        '${trackTitle}' => $leftTrack->title,
        '${mp3Url}'     => $leftTrack->getPreviewMp3Url(),
        '${compPoints}' => $leftTrack->competition_points
    ));
}

function buildRightPlayer(&$rightTrack) {
    return processTpl('Startpage/player.html', array(
        '${playerId}'   => 2,
        '${trackId}'    => $rightTrack->id,
        '${trackTitle}' => $rightTrack->title,
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

?>