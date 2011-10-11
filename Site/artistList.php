<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
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
}

$latestArtistsList = '';
$latestArtists = User::fetch_all_from_to(0, 999999, false, false, false, 'order by u.entry_date desc'); // FIXME - paging?
foreach ($latestArtists as $a) {
    $latestArtistsList .= processTpl('ArtistList/artistListItem.html', array(
        '${artistImgUrl}' => getUserImageUri($a->image_filename, 'tiny'),
        '${userId}'       => $a->id,
        '${artistName}'   => escape($a->name)
    ));
}

$topArtistsList = '';
$topArtists = User::fetch_most_listened_artists_from_to(0, 999999); // FIXME - paging?
foreach ($topArtists as $a) {
    $topArtistsList .= processTpl('ArtistList/artistListItem.html', array(
        '${artistImgUrl}' => getUserImageUri($a->image_filename, 'tiny'),
        '${userId}'       => $a->id,
        '${artistName}'   => escape($a->name)
    ));
}

processAndPrintTpl('ArtistList/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Artist list', true, false),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${ArtistList/artistListItem_top_list}'    => $topArtistsList,
    '${ArtistList/artistListItem_latest_list}' => $latestArtistsList,
    '${Common/newsletterSubscription}'         => processTpl('Common/newsletterSubscription.html', array()),
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>        	