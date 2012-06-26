<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');

processAndPrintTpl('ArtistAgreement/index.html', array(
    '${Common/pageHeader}' => buildPageHeader('Artist agreement'),
    '${Common/bodyHeader}' => buildBodyHeader(null),
    '${Common/bodyFooter}' => buildBodyFooter(),
    '${Common/pageFooter}' => buildPageFooter()
));

// END

?>      
