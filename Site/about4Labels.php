<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/EditorInfo.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie();

$editorInfo = EditorInfo::fetchForId($EDITOR_INFO_ID_ABOUT_TEXT_4_LABELS);
if (!$editorInfo) $htmlContent = $MISSING_EDITOR_INFO_TEXT;
else              $htmlContent = $editorInfo->html;

processAndPrintTpl('About4Labels/index.html', array(
    '${Common/pageHeader}' => buildPageHeader('Oneloudr for labels'),
    '${Common/bodyHeader}' => buildBodyHeader($user),
    '${htmlContent}'       => $htmlContent,
    '${Common/bodyFooter}' => buildBodyFooter(),
    '${Common/pageFooter}' => buildPageFooter()
));

// END

?>
