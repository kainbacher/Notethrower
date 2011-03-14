<?php

include_once('../Includes/Init.php');
include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');

$myUrl = 'http://notethrower.com/NTTest/Site/fb.php'; // FIXME #################

$code = $_REQUEST['code'];

if(empty($code)) {
    $dialogUrl = "http://www.facebook.com/dialog/oauth?client_id=" .
        $GLOBALS['FACEBOOK_APP_ID'] . "&redirect_uri=" . urlencode($myUrl) .
        '&scope=email';

    redirectTo($dialogUrl);

} else {
    $tokenUrl = "https://graph.facebook.com/oauth/access_token?client_id="
        . $GLOBALS['FACEBOOK_APP_ID'] . "&redirect_uri=" . urlencode($myUrl) . "&client_secret="
        . $GLOBALS['FACEBOOK_APP_SECRET'] . "&code=" . $code;

    $accessToken = file_get_contents($tokenUrl);

    redirectTo('/NTTest/Site/startpageNew.php?' . $accessToken); // FIXME #################
}

?>