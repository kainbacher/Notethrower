<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/AudioTrack.php');
//include_once('../Includes/DB/News.php');
include_once('../Includes/DB/User.php');

$visitorUserId = -1;

$userIsLoggedIn = false;
$user = User::new_from_cookie();
if ($user) {
    $visitorUserId = $user->id;
    $logger->info('visitor user id: ' . $visitorUserId);

    $userIsLoggedIn = true;
    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');

    if (get_param('action') == 'login') {
        $logger->info('login request received');
        if (get_param('username') && get_param('password')) {
            $user = User::fetch_for_username_password(get_param('username'), get_param('password'));
            if ($user && $user->status == 'active') {
                $user->doLogin();
                $logger->info('login successful, reloading page to set cookie');
                redirectTo($_SERVER['PHP_SELF']);

            } else {
                $logger->info('login failed');
            }

        } else {
            $logger->info('username and/or password missing');
        }
    }
}

//$trackCount = AudioTrack::count_all(false, false, $visitorUserId);
//$logger->info('track count: ' . $trackCount);

//$newsCount = News::count_all();

$latestTracksList = '';
$latestTracks = AudioTrack::fetch_newest_from_to(0, 5, false, false, $visitorUserId);

foreach ($latestTracks as $track) {
    $latestTracksList .= processTpl('Index/trackListItem.html', array(
        '${artistName}' => escape($track->user_name),
        '${trackTitle}' => escape($track->title),
    ));
}

if (!$latestTracksList) $latestTracksList = 'No tracks found.';

$topTracksList = '';
$topTracks = AudioTrack::fetch_most_downloaded_from_to(0, 5, false, false, $visitorUserId);

foreach ($latestTracks as $track) {
    $topTracksList .= processTpl('Index/trackListItem.html', array(
        '${artistName}' => escape($track->user_name),
        '${trackTitle}' => escape($track->title),
    ));
}

if (!$topTracksList) $topTracksList = 'No tracks found.';

processAndPrintTpl('Index/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Start', true, true),
    '${Common/bodyHeader}'                     => buildBodyHeader(),
    '${Index/trackListItem_latestTracks_list}' => $latestTracksList,
    '${Index/trackListItem_topTracks_list}'    => $topTracksList,
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>        	