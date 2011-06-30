<?php

include_once('../Includes/Init.php');// must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');
include_once('../Includes/DB/User.php');
include_once('../Includes/DB/News.php');

// FIXME - templateisieren und news-datum mit anzeigen!

$user = User::new_from_cookie();

$newsCount = News::count_all();

processAndPrintTpl('Blog/index.html', array(
    '${Common/pageHeader}' => buildPageHeader('Blog', false, true),
    '${Common/bodyHeader}' => buildBodyHeader($user),
    '${newsCount}'         => $newsCount,
    '${newsPerPage}'       => $GLOBALS['NEWS_PER_PAGE'],
    '${Common/bodyFooter}' => buildBodyFooter(),
    '${Common/pageFooter}' => buildPageFooter()
));

// END

?>