<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');

processAndPrintTpl('TermsAndConditions/index.html', array(
    '${Common/pageHeader}'                     => buildPageHeader('Terms and conditions'),
    '${Common/bodyHeader}'                     => buildBodyHeader(null),
    '${Common/bodyFooter}'                     => buildBodyFooter(),
    '${Common/pageFooter}'                     => buildPageFooter()
));

// END

?>      