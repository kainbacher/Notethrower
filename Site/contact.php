<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie();

// FIXME - selber form und versand programmieren - google include rausschmeissen.

processAndPrintTpl('Contact/index.html', array(
    '${Common/pageHeader}' => buildPageHeader('Contact'),
    '${Common/bodyHeader}' => buildBodyHeader($user),
    '${Common/bodyFooter}' => buildBodyFooter(),
    '${Common/pageFooter}' => buildPageFooter()
));

// END