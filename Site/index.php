<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/RemoteSystemCommunicationUtil.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/Project.php');
include_once('../Includes/DB/User.php');

// FIXME - voting system, players, etc. need to be made dynamic. try not to reload the entire page when voting happens. player code is in startpage.php.

//$logger->set_debug_level();

$visitorUserId = -1;
$user = handleAuthentication();

if ($user) {
    $visitorUserId = $user->id;
    $logger->info('visitor user id: ' . $visitorUserId);
}

$latestTracksList = '';
$latestTracks = Project::fetch_newest_from_to(0, 5, false, false, $visitorUserId);

foreach ($latestTracks as $track) {
    $latestTracksList .= processTpl('Index/trackListItem.html', array(
        '${artistImgUrl}' => getUserImageUri($track->user_img_filename, 'tiny'),
        '${artistName}'   => escape($track->user_name),
        '${trackId}'      => $track->id,
        '${trackTitle}'   => escape($track->title),
    ));
}

if (!$latestTracksList) $latestTracksList = 'No tracks found.';

$topTracksList = '';
$topTracks = Project::fetch_most_downloaded_from_to(0, 5, false, false, $visitorUserId);

foreach ($topTracks as $track) {
    $topTracksList .= processTpl('Index/trackListItem.html', array(
        '${artistImgUrl}' => getUserImageUri($track->user_img_filename, 'tiny'),
        '${artistName}'   => escape($track->user_name),
        '${trackId}'      => $track->id,
        '${trackTitle}'   => escape($track->title),
    ));
}

if (!$topTracksList) $topTracksList = 'No tracks found.';

processAndPrintTpl('Index/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Start', 'circlesmall', false),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${Index/trackListItem_latestTracks_list}' => $latestTracksList,
    '${Index/trackListItem_topTracks_list}'    => $topTracksList,
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

// END

// functions
function handleAuthentication() {
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
        if (get_param('username') && get_param('password')) {
            $user = User::fetch_for_email_address_and_password(get_param('username'), get_param('password'));
            if ($user && $user->status == 'active') {
                $user->doLogin();
                $logger->info('login via email successful, reloading page to set cookie');
                redirectTo($GLOBALS['BASE_URL'] . 'Site/dashboard.php');

            } else {
                $logger->info('login via email failed, trying via username');

                $user = User::fetch_for_username_password(get_param('username'), get_param('password'));
                if ($user && $user->status == 'active') {
                    $user->doLogin();
                    $logger->info('login via username successful, reloading page to set cookie');
                    redirectTo($GLOBALS['BASE_URL'] . 'Site/dashboard.php');

                } else {
                    $logger->info('login via username failed, too');
                    return null;
                }
            }

        } else {
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
            $logger->info(print_r($fbUserData, true));

            // if user data complete, log user in
            $user = User::fetch_for_email_address($fbUserData->email);
            if ($user && $user->status == 'active') {
                $user->doLogin();
                $logger->info('facebook login successful, reloading page to set cookie');
                redirectTo($GLOBALS['BASE_URL'] . 'Site/dashboard.php');

            } else { // user not found -> this either means the user doesn't exist here yet or he was not found with the facebook email address
                // the only thing we can do here is redirect the user to the signup page
                // FIXME - which page mode? fan or artist or shall we redirect to the selection page (as soon as we have one)?
                redirectTo('account.php' .
                        '?email_address=' . urlencode($fbUserData->email) .
                        '&facebook_id='   . urlencode($fbUserData->id) .
                        '&facebook_url='  . urlencode($fbUserData->link) .
                        '&webpage_url='   . urlencode($fbUserData->user_website) .
                        '&username='      . urlencode($fbUserData->email));
            }

        } else {
            $logger->error('failed to get user information from facebook: ' . $resp['error']);
        }
    }

    return null;
}

?>        	