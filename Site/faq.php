<?php

include_once('../Includes/Init.php');  // must be included first
include_once('../Includes/Config.php');
include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');

$user = User::new_from_cookie();

processAndPrintTpl('FAQ/index.html', array(
    '${Common/pageHeader}'                    => buildPageHeader('FAQ'),
    '${Common/bodyHeader}'                    => buildBodyHeader($user),
    '${Common/bodyFooter}'                    => buildBodyFooter(),
    '${Common/pageFooter}'                    => buildPageFooter()
));

// END
