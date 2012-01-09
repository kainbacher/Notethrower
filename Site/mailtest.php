<?php

include_once('../Includes/Init.php'); // must be included first

include_once('../Includes/Config.php');

$GLOBALS['MAIL_FROM_NAME'] = 'noreply-oneloudr';
$GLOBALS['MAIL_FROM_ADDRESS'] = 'noreply@oneloudr.com';
$GLOBALS['MAIL_REPLY_TO_ADDRESS'] = 'noreply@oneloudr.com';
$GLOBALS['EMAIL_DELIVERY_MODE'] = 'active';
$GLOBALS['EMAIL_DELIVERY_OVERRIDE_ALLOWED_RECIPIENTS'] = array();
$GLOBALS['EMAIL_DELIVERY_OVERRIDE_ADDR'] = 'hjonas@gmx.at';
$GLOBALS['BCC_EMAIL_ADDRESS'] = '';
$GLOBALS['MAIL_SERVER_HOST'] = 'smtp.sendgrid.net';
$GLOBALS['MAIL_SERVER_PORT'] = 25;
$GLOBALS['MAIL_SERVER_AUTH_TYPE'] = 'basic';
$GLOBALS['MAIL_SERVER_AUTH_USER'] = 'oneloudr';
$GLOBALS['MAIL_SERVER_AUTH_PWD'] = 'oneloudr';

include_once('../Includes/MailUtil.php');

$msg = "hey, \n\njust a quick test if this whole email delivery thing really works\nin a reliable way ... cu,\n the test script.";

$recipients = array(
  'hjonas@gmx.at',
  'hanno@rastaduck.org',
  'hanno.jonas@gmail.com',
  'hanno@jonas-it.com',
  'hanno.jonas@vol.at'
);

foreach ($recipients as $rec) {
  sendEmail(
    $rec,
    'subject',
    $msg 
  );
}

?>
