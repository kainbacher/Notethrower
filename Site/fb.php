<?php

include_once('../Includes/Init.php');
include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');

$myUrl = $GLOBALS['BASE_URL'] . 'Site/fb.php';

$code    = get_param('code');
$destUrl = get_param('destUrl');

if (!$code) {
    $dialogUrl = 'http://www.facebook.com/dialog/oauth?client_id=' .
            $GLOBALS['FACEBOOK_APP_ID'] . '&redirect_uri=' . urlencode($myUrl) .
            '&scope=email&destUrl=' . urlencode(get_param('destUrl'));

    redirectTo($dialogUrl);

} else {
    $tokenUrl = 'https://graph.facebook.com/oauth/access_token?client_id=' .
            $GLOBALS['FACEBOOK_APP_ID'] . '&redirect_uri=' . urlencode($myUrl) . '&client_secret=' .
            $GLOBALS['FACEBOOK_APP_SECRET'] . '&code=' . $code;

    $accessToken = file_get_contents($tokenUrl);

    if ($destUrl) {
        $concatChar = strpos($destUrl, '?') !== false ? '&' : '?';
        redirectTo($destUrl . $concatChar . $accessToken);

    } else {
        redirectTo($GLOBALS['BASE_URL'] . 'Site/index.php?' . $accessToken);
    }
}

?>