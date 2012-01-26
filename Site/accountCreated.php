<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Snippets.php');
include_once('../Includes/TemplateUtil.php');

$email = get_param('email');
if (!email_syntax_ok($email)) $email = '';

processAndPrintTpl('AccountCreated/index.html', array(
    '${Common/pageHeader}' => buildPageHeader('Account created'),
    '${Common/bodyHeader}' => buildBodyHeader(null),
    '${emailAddress}'      => escape($email),
    '${Common/bodyFooter}' => buildBodyFooter(),
    '${Common/pageFooter}' => buildPageFooter()
));

// END

?>