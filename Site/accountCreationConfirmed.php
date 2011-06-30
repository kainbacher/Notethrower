<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');

if (isset($_GET['x']) && isset($_GET['c']) && md5('TheSparrowsAreFlyingAgain!' . $_GET['x']) == $_GET['c']) {
    $logger->info('activation requested for account: ' . $_GET['x']);

    $user = User::fetch_for_id($_GET['x']);
    if (!$user) {
        $logger->warn('user not found: ' . $_GET['x']);
        exit;

    } else if ($user->status != 'inactive') { // make sure that eg a banned used cannot reactivate his account be clicking the confirmation link again
        $logger->warn('user was found but status was not "inactive", exit');
        exit;
    }

    $user->status = 'active';
    $user->save();

} else {
    $logger->warn('Invalid confirmation request: ' . $_SERVER['QUERY_STRING']);
    exit;
}

$user = User::new_from_cookie();

processAndPrintTpl('AccountCreated/index.html', array(
    '${Common/pageHeader}' => buildPageHeader('Account created'),
    '${Common/bodyHeader}' => buildBodyHeader($user),
    '${Common/bodyFooter}' => buildBodyFooter(),
    '${Common/pageFooter}' => buildPageFooter()
));

// END

?>                    