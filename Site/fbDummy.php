<?php

// a dummy script which simulates the behaviour of the real fb.php script

include_once('../Includes/Init.php');
include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/DB/User.php');

if ($GLOBALS['STAGING_ENV'] == 'dev') {
    $uid     = get_numeric_param('uid');
    $destUrl = get_param('destUrl');

    if ($uid) {
        $user = User::fetch_for_id($uid);
        $user->doLogin();

        if ($destUrl) {
            redirectTo($destUrl);
        } else {
            redirectTo($GLOBALS['BASE_URL'] . 'Site/index.php');
        }

    } else {
        echo 'FACEBOOK SIGN IN DUMMY<br><br>' . "\n";

        $users = User::fetch_all_from_to(0, 999, false, false);
        foreach ($users as $user) {
            echo '<a href="' . $_SERVER['PHP_SELF'] . '?uid=' . $user->id . '">Sign in as user "' . escape($user->username) . '"</a><br>' . "\n";
        }
        exit;
    }

} else { // for non-dev environments redirect to the real fb.php script
    redirectTo($GLOBALS['BASE_URL'] . 'Site/fb.php');
}

?>