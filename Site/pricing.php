<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');

$visitorUserId = -1;

$user = User::new_from_cookie();
if ($user) {
    $visitorUserId = $user->id;
    $logger->info('visitor user id: ' . $visitorUserId);

    $logger->info('user is logged in');

} else {
    $logger->info('user is NOT logged in');
}

processAndPrintTpl('Pricing/index.html', array( // ################## hier ordner anpassen!
    '${Common/pageHeader}'                     => buildPageHeader('FIXME', true),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>        	