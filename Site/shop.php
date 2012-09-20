<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/EditorInfo.php');
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

$editorInfo = EditorInfo::fetchForId($EDITOR_INFO_ID_SHOP);
if (!$editorInfo) $htmlContent = $MISSING_EDITOR_INFO_TEXT;
else              $htmlContent = $editorInfo->html;

processAndPrintTpl('Shop/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Shop'),
    '${Common/bodyHeader}'                     => buildBodyHeader($user),
    '${htmlContent}'                           => $htmlContent,
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

?>
