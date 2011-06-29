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

$artistList = '';
$artists = User::fetch_all_from_to(0, 999999, false, false, false); // FIXME - paging?
foreach ($artists as $a) {
    $artistList .= processTpl('Artists/artistListItem.html', array(
        '${artistImgUrl}' => getUserImageUri($a->image_filename, 'tiny'),
        '${userId}'       => $a->id,
        '${artistName}'   => escape($a->name)
    ));
}

processAndPrintTpl('Artists/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Artists', true),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${Artists/artistListItem_list}'           => $artistList,
    '${Common/newsletterSubscription}'         => processTpl('Common/newsletterSubscription.html', array()),
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>        	