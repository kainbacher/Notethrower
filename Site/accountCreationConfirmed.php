<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');

if (isset($_GET['x']) && isset($_GET['c']) && md5('TheSparrowsAreFlyingAgain!' . $_GET['x']) == $_GET['c']) {
    $logger->info('activation requested for account: ' . $_GET['x']);

    $user = User::fetch_for_id($_GET['x']);
    if (!$user) {
        $logger->warn('user not found: ' . $_GET['x']);
        exit;

    } else if ($user->status != 'active' && $user->status != 'inactive') { // make sure that eg a banned used cannot reactivate his account be clicking the confirmation link again
        $logger->warn('user was found but status was not "inactive", exit');
        exit;
    }

    $user->status = 'active';
    $user->save();
    
    $logger->info('activated user account');
    
    $user->doLogin();
    $logger->info('user will be automatically logged in, reloading page to set cookie');
    redirectTo(basename($_SERVER['PHP_SELF'], '.php') . '?ok=1');   

} else if (get_param('ok') == 1) {
    // second step after confirmation and login
    
    $user = User::new_from_cookie();

    processAndPrintTpl('AccountCreationConfirmed/index.html', array(
        '${Common/pageHeader}' => buildPageHeader('Account creation confirmed'),
        '${Common/bodyHeader}' => buildBodyHeader($user),
        '${Common/bodyFooter}' => buildBodyFooter(),
        '${Common/pageFooter}' => buildPageFooter()
    ));
    
} else {
    $logger->warn('Invalid confirmation request: ' . $_SERVER['QUERY_STRING']);
}

// END

?>                    
