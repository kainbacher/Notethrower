<?php

include_once('../Includes/Init.php');
include_once('../Includes/Config.php');

$my_url = 'http://notethrower.com/NTTest/Site/fb.php';

$code = $_REQUEST['code'];

if(empty($code)) {
    $dialog_url = "http://www.facebook.com/dialog/oauth?client_id=" .
        $GLOBALS['FACEBOOK_APP_ID'] . "&redirect_uri=" . urlencode($my_url) .
        '&scope=email';

    //echo("<script> top.location.href='" . $dialog_url . "'</script>");
    header('Location: ' . $dialog_url);
    exit;
}

$token_url = "https://graph.facebook.com/oauth/access_token?client_id="
    . $GLOBALS['FACEBOOK_APP_ID'] . "&redirect_uri=" . urlencode($my_url) . "&client_secret="
    . $GLOBALS['FACEBOOK_APP_SECRET'] . "&code=" . $code;

$access_token = file_get_contents($token_url);

header('Location: /NTTest/Site/startpageNew.php?' . $access_token);
exit;

//$graph_url = "https://graph.facebook.com/me?" . $access_token;

//$user = json_decode(file_get_contents($graph_url));

//header('content-type: text/plain');

//echo("Hello " . $user->name . ' (' . $user->email . ')');

//print_r($user);

?>